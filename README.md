# Azeroth Events

Demo didática de **Event-Driven Architecture** em Laravel, usando o mesmo
tema da talk: um boss de WoW é derrotado e vários sistemas reagem ao fato
sem se conhecerem entre si.

Feita pra acompanhar a apresentação *"Event Driven Architecture com PHP"*
no PHP Conference — cada pedaço de código aqui tem uma correspondência
direta com um slide. A seção [De slide pra código](#de-slide-pra-código)
no final mapeia isso.

## O cenário

`Nikolayevsk` (ou qualquer personagem) derrota um boss. Isso dispara o
evento `BossKilled`, que é só um **fato** ("o boss morreu") — ele não sabe
nem se importa com quem vai reagir. Quatro consumidores reagem a esse
mesmo fato, de formas diferentes:

| Listener | Quando roda | O que faz |
|---|---|---|
| `LogCombatEvent` | síncrono | grava o fato no Combat Log (nosso event store) |
| `GrantExperience` | síncrono | concede XP na hora |
| `UnlockAchievement` | síncrono | libera conquista — **idempotente** |
| `AwardLoot` | **assíncrono** (`ShouldQueue`) | simula uma chamada lenta e distribui ouro, só quando o worker da fila pegar |

Isso é literalmente o slide **Publish-Subscribe**: "vários serviços
precisam reagir ao mesmo fato" + o slide **Comunicação em EDA**: "um
evento pode ser tratado no mesmo processo ou enviado para filas".

## Como rodar

```bash
cd azeroth-events
cp .env.example .env   # se ainda não existir
php artisan migrate:fresh
```

Não precisa Redis/RabbitMQ pra essa demo: a fila usa o driver `database`
(tabela `jobs`), já configurado no `.env`.

### 1. Veja os listeners reagindo ao vivo

Abra dois terminais extras:

```bash
# Terminal 2: tail bonito de log (já vem instalado por padrão no Laravel 12)
php artisan pail

# Terminal 3: worker da fila, pra processar o AwardLoot (assíncrono)
php artisan queue:work
```

### 2. Dispare o evento

No terminal principal:

```bash
php artisan wow:boss-kill Nikolayevsk "Lich King"
```

No `pail` (terminal 2) você verá, **na hora**:

```
📜 [LogCombatEvent] Combat log gravado...
⚔️  [GrantExperience] +250 XP...
🏆 [UnlockAchievement] Conquista liberada...
```

E alguns segundos depois, no `queue:work` (terminal 3) processando:

```
💰 [AwardLoot] (assíncrono, via fila) ... saqueou X de ouro...
```

Esse atraso é **de propósito** (`sleep(2)` dentro do listener) — simula
uma dependência mais pesada (ex: serviço externo de itens) e mostra, ao
vivo, por que o publisher não fica esperando essa parte terminar.

### 3. Simule uma entrega duplicada (idempotência)

O comando acima imprime um `eventId`. Copie e rode:

```bash
php artisan wow:replay-boss-kill <eventId>
```

Isso redispara o **mesmo** evento, com o mesmo `eventId` — exatamente como
um broker real faria em um retry/timeout de ack. No `pail` você verá todos
os listeners dizendo `♻️ já processado — ignorando`, em vez de dar XP,
ouro e conquista em dobro. Esse é o slide **"E se o evento chegar duas
vezes?"** rodando de verdade.

### 4. Bônus: Event Sourcing

```bash
php artisan wow:character-sheet Nikolayevsk
```

Mostra a ficha "oficial" (colunas `xp`/`gold` da tabela `characters`) lado
a lado com a ficha **recalculada do zero** usando só o Combat Log (nosso
event store) — e os dois batem. É a prova em código do slide: "o estado
não vem do banco, mas sim da sequência de eventos".

### 5. Teste você mesmo pela HTTP (não testado em CI, pra você rodar ao vivo)

Suba o servidor:

```bash
php artisan serve
```

Em outro terminal:

```bash
curl -s -X POST http://127.0.0.1:8000/api/boss-kill \
  -H "Content-Type: application/json" \
  -d '{"character":"Thalindra","boss":"Illidan"}'
```

A resposta traz o `event_id` e já sugere o comando de replay. Pra ver a
ficha reconstruída via HTTP:

```bash
curl -s http://127.0.0.1:8000/api/characters/Thalindra/sheet
```

Se quiser repetir o teste de reentrega pela HTTP também, chame o mesmo
`POST /api/boss-kill` passando o mesmo `character`+`boss` — vai criar um
`eventId` novo (esse endpoint sempre gera um novo fato), então pra testar
idempotência via HTTP de verdade seria preciso expor um endpoint de
replay também; por ora o replay fica só no artisan (`wow:replay-boss-kill`),
que é o que a talk usa ao vivo.

## De slide pra código

| Slide da talk | Onde está no código |
|---|---|
| Evento ≠ Mensagem | `app/Events/BossKilled.php` é o evento (semântica); o registro na tabela `jobs` quando `AwardLoot` é enfileirado é a mensagem (transporte) |
| Producer / Dispatch / Consumer | Producer = `BossKillCommand` / `BossKillController`; Dispatch = `Event::listen(...)` em `AppServiceProvider::boot()`; Consumer = os 4 Listeners |
| Publish-Subscribe | `AppServiceProvider::boot()` — um evento, quatro listeners, nenhum conhece o outro |
| Síncrono vs Assíncrono | `LogCombatEvent`/`GrantExperience`/`UnlockAchievement` (sync) vs `AwardLoot implements ShouldQueue` (async) |
| E se o evento chegar duas vezes? | `app/Models/ProcessedEvent.php` + `wow:replay-boss-kill` |
| Event Sourcing | `CombatLogEntry` (event store) + `wow:character-sheet` (replay) |
| EDA também tem custo (complexidade, debug distribuído) | compare o `AwardLoot` (precisa olhar fila + worker + logs em processos diferentes) com os listeners síncronos (tudo no mesmo request) |

## Notas

- Projeto gerado com `composer create-project laravel/laravel` (Laravel 12).
- Banco: SQLite (`database/database.sqlite`), zero configuração.
- Auto-discovery de listeners do Laravel foi **desligado** de propósito em
  `bootstrap/app.php` (`->withEvents(discover: false)`), pra que o registro
  explícito em `AppServiceProvider::boot()` seja a única fonte de verdade
  — isso também evita listener duplicado reagindo duas vezes ao mesmo
  evento por engano (achado durante o desenvolvimento desta demo).
