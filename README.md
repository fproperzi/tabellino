# Tabellino live

Web app per telefono e tablet con cui si registrano, a bordo campo, gli eventi di una partita di rugby usando i **numeri di maglia** e il **minuto**. A fine partita genera il **tabellino squadra nel formato FIR del modello Serie A Elite 2026**, con i corsivi previsti, pronto da copiare in Word o in una mail.

Nasce per sostituire il foglio cartaceo: invece di mettere una crocetta sul punteggio, si tocca il numero del giocatore e l'app ricostruisce da sola marcatori con parziali, catene di sostituzioni, cartellini e percentuali dei calciatori.

- Versione attuale: **v1.15**
- Stack: HTML + JavaScript senza dipendenze, PHP 8.2+, SQLite
- Funziona anche senza rete: i dati restano sul dispositivo e si inviano al server quando si vuole
- Installazione con semplice copia dei file: utenti e password si creano dal browser

**Demo pubblica, senza installare nulla: [tabellino.infinityfree.io](https://tabellino.infinityfree.io)** — utenti `demo1`...`demo5`, password `TabellinoDemo1!` (uguale per tutti, nessuno è amministratore). Si resetta a ogni pubblicazione di una nuova versione: non usarla per salvare qualcosa a lungo termine.

---

## Indice

1. [Funzionalità](#funzionalità)
2. [Demo pubblica](#demo-pubblica)
3. [Requisiti](#requisiti)
4. [Installazione](#installazione)
5. [Configurare utenti e password](#configurare-utenti-e-password)
6. [Sicurezza](#sicurezza)
7. [Uso durante la partita](#uso-durante-la-partita)
8. [Come vengono gestiti i minuti](#come-vengono-gestiti-i-minuti)
9. [Sostituzioni e casi particolari](#sostituzioni-e-casi-particolari)
10. [Il tabellino generato](#il-tabellino-generato)
11. [Struttura dei file](#struttura-dei-file)
12. [API](#api)
13. [Modello dei dati](#modello-dei-dati)
14. [Partita di prova](#partita-di-prova)
15. [Limiti noti](#limiti-noti)
16. [Cronologia versioni](#cronologia-versioni)

---

## Funzionalità

**Durante la partita**

- Tabellone sempre visibile con punteggio e cronometro (avvio, pausa, correzione del minuto)
- Due pulsanti grandi per passare da una squadra all'altra, con tasto ⇄ per invertire i lati a seconda di dove si trova chi scrive
- Azioni con un tocco: meta, meta tecnica, trasformazione centrata/sbagliata, calcio piazzato centrato/sbagliato, drop, cartellino giallo, cartellino rosso, sostituzione, sostituzione temporanea
- Selezione del giocatore da una griglia con numero e cognome; per le sostituzioni i giocatori non in campo (o già in campo) appaiono attenuati
- Dopo una meta l'app propone subito la trasformazione, con l'ultimo calciatore già evidenziato
- Promemoria per le sostituzioni temporanee aperte (con tasto "Rientra ora") e per i gialli in corso
- Ogni evento può essere assegnato al primo o al secondo tempo anche dopo, e il minuto è sempre modificabile

**Dopo la partita**

- Lista cronologica degli eventi, ognuno correggibile con un solo tasto **Modifica**: minuto, tempo e giocatore/i (per le sostituzioni entrambi, con lo stesso selettore usato in fase di registrazione), più l'eliminazione
- Tabellino nel formato del modello FIR Serie A Elite 2026 (vedi [Il tabellino generato](#il-tabellino-generato))
- Copia con formattazione, copia solo testo, download `.doc`
- Salvataggio e riapertura delle partite dal server; esportazione e importazione in JSON per backup o per spostare una partita da un'altra installazione

**Dati di contorno**

- Luogo, data, campionato, giornata, spettatori, note
- Ufficiali di gara: arbitro, AA1, AA2, quarto uomo, TMO
- Punti in classifica, Player of the Match con dicitura personalizzabile (es. "Simecom Player of the Match")
- Formazioni da 23 giocatori con capitano, inseribili a mano o incollando un elenco di testo

---

## Demo pubblica

**[tabellino.infinityfree.io](https://tabellino.infinityfree.io)** — per farsi un'idea senza installare nulla.

| | |
|---|---|
| Utenti | `demo1`, `demo2`, `demo3`, `demo4`, `demo5` |
| Password | `TabellinoDemo1!` (uguale per tutti) |

Nessuno di questi utenti è amministratore: non si possono creare o eliminare altri utenti. La demo riparte pulita (partita di prova ricaricata, tutto il resto cancellato) a ogni pubblicazione di una nuova versione — se qualcun altro la sta usando in quel momento, perde quello che ha fatto. Non è pensata per un uso vero, solo per provare l'interfaccia.

---

## Requisiti

| Componente | Versione | Note |
|---|---|---|
| PHP | 8.2 consigliato (minimo 7.4) | Qualsiasi hosting PHP recente va bene |
| Estensione PHP | `pdo_sqlite` | Presente di default nella maggior parte degli hosting; non serve un database MySQL |
| Web server | Apache 2.4 consigliato | Gli `.htaccess` di protezione sono scritti per Apache |
| Browser | Chrome, Safari, Firefox, Edge recenti | Serve JavaScript abilitato |
| Connessione | HTTPS consigliato | Necessario per la copia con formattazione e per i cookie `secure` |

Non servono Composer, Node, build o librerie esterne.

> **Se il tuo hosting è ancora su PHP 7.4, è ora di aggiornare.** L'app funziona anche lì, ma quella versione è in **EOL dal novembre 2022**: da anni non riceve più nessuna patch di sicurezza, nemmeno per falle critiche. Il minimo supportato resta 7.4 solo per chi non ha altra scelta nell'immediato; appena il provider lo permette, passa a una versione più recente (8.2 consigliata).

---

## Installazione

Non serve saper programmare: basta copiare i file.

1. **Copia la cartella** sul tuo spazio web, per esempio in `tabellino/`, con il pannello del provider o un programma FTP (FileZilla).
2. **Apri subito l'indirizzo** nel browser, per esempio `https://tuodominio.it/tabellino/`.
3. **Crea l'amministratore**: al primo avvio compare la schermata "Benvenuto". Scegli un nome utente e una password (almeno 8 caratteri) e ripetila.
4. **Partita di prova**: lascia la spunta su "Carica una partita di prova" se vuoi vedere subito come funziona l'app con una partita già compilata (Rovigo v Viadana 22-18). Togli la spunta per partire da un archivio vuoto. Premi "Crea amministratore ed entra".

Fatto. L'app crea da sola la cartella `data/`, il database, le sessioni e la protezione `data/.htaccess`. Se hai chiesto la partita di prova, la trovi in **Partita → Apri dal server**.

> **Fai il passo 3 appena hai caricato i file.** Finché l'amministratore non esiste, chiunque apra l'indirizzo vede la schermata di creazione. Appena creato il primo utente, quella schermata sparisce per sempre.

> Se nella cartella è rimasto un vecchio `index.html`, cancellalo: il server potrebbe mostrarlo al posto di `index.php`, aggirando il login.

### Se compare un errore sui permessi

L'app deve poter scrivere nella propria cartella. Al primo avvio, prima ancora di mostrare "Benvenuto", controlla da sola di potercela fare: se qualcosa non va (permessi, o manca l'estensione PHP `pdo_sqlite`) lo dice subito con un pannello che spiega cosa correggere, con un link per ricaricare la pagina e riprovare.

Il caso più comune resta i permessi: dal pannello del provider o da FileZilla (tasto destro → Permessi file) dai alla cartella `tabellino` i permessi **755** oppure **775**, poi ricarica.

### Installazione in locale (XAMPP)

Copia la cartella in `C:\xampp\htdocs\tabellino\` e apri `http://localhost/tabellino/`. Verifica in `php.ini` che la riga `extension=pdo_sqlite` non inizi con `;`.

---

## Configurare utenti e password

Tutto si fa dal browser: il tasto **+** in basso a destra (visibile da qualsiasi scheda) si apre in un menu con **Utenti e password** (pagina `utenti.php`) ed **Esci**.

| Chi | Cosa può fare |
|---|---|
| Ogni utente | Cambiare la propria password (serve quella attuale) |
| Amministratore | Aggiungere utenti, decidere chi è amministratore, reimpostare la password di chi l'ha dimenticata, eliminare utenti |

Regole:

- Nome utente da 3 a 32 caratteri: lettere, numeri, punto, trattino, underscore.
- Password di almeno 8 caratteri.
- Non si può eliminare il proprio utente né l'ultimo amministratore.
- Un utente eliminato viene scollegato subito da tutti i dispositivi.

Le password non vengono mai salvate: nel file `data/users.json` c'è solo il loro hash (bcrypt), protetto dall'accesso via web.

### Password dell'amministratore dimenticata

Se esiste un altro amministratore, può reimpostarla da `utenti.php`. Altrimenti, via FTP cancella il file `data/users.json`: al successivo accesso ricompare la schermata "Benvenuto" per creare un nuovo amministratore. Le partite salvate non vengono toccate. Anche in questo caso completa subito la creazione.

### Parametri avanzati

In `auth.php`, per chi vuole modificarli:

| Costante | Default | Significato |
|---|---|---|
| `AUTH_LIFETIME` | `43200` | Secondi di inattività prima di dover rifare il login (12 ore) |
| `AUTH_MAX_FAILS` | `5` | Tentativi errati consentiti per IP |
| `AUTH_LOCK_SECONDS` | `900` | Durata del blocco dopo troppi errori (15 minuti) |
| `AUTH_MIN_PASSWORD` | `8` | Lunghezza minima della password |

### Aggiornamento da v1.02–v1.05

Nelle versioni precedenti l'hash della password era scritto dentro `auth.php`. Il nuovo `auth.php` ha l'elenco vuoto, quindi dopo averlo caricato l'app mostrerebbe la schermata di primo avvio. Due possibilità:

- **La più semplice:** carica i nuovi file e apri subito `login.php` per creare l'amministratore.
- **Per conservare la password attuale:** prima di caricare il nuovo `auth.php`, copia la riga con il tuo hash dal vecchio file dentro `const AUTH_USERS = [];`, per esempio `const AUTH_USERS = ['kino' => '$2y$10$...'];`. Al primo accesso l'utente viene importato in `data/users.json` come amministratore; poi puoi svuotare di nuovo l'elenco.

---

## Sicurezza

### Protezione della cartella dati

L'app crea automaticamente `data/.htaccess`, che su Apache nega ogni accesso via web a database, utenti e sessioni.

**Verifica consigliata:** aprendo `https://tuodominio/tabellino/data/users.json` il server deve rispondere **403 Forbidden**. Se invece vedi il contenuto del file, il tuo hosting non usa gli `.htaccess` (tipico di nginx): chiedi al provider di bloccare l'accesso alla cartella `data`.

Come seconda barriera, il repository include già un `.htaccess` nella cartella principale che nega l'accesso via web a file di database/dati, a `deploy.ini` e alle cartelle di git, oltre a disattivare l'elenco delle cartelle. Se `Options -Indexes` provoca un errore 500 sul tuo hosting, togli quella riga.

Chi gestisce il proprio server può spostare il database fuori dalla document root modificando `DB_PATH` in `db.php`; controlla che `open_basedir` includa il nuovo percorso.

### Cosa fa l'autenticazione

- Utenti e hash delle password in `data/users.json`, creati e gestiti dal browser
- Login a sessione con cookie `HttpOnly`, `SameSite=Lax` e `Secure` quando il sito è in HTTPS
- Sessioni salvate in `data/sessions/`, così altre app PHP sullo stesso server non possono cancellarle
- Token CSRF su tutti i moduli (login, logout, gestione utenti)
- Blocco per IP dopo troppi tentativi errati
- `api.php` risponde `401` a qualsiasi richiesta non autenticata
- Pagine marcate `noindex, nofollow`

### Proprietà dei tabellini

- Ogni tabellino salvato sul server è legato a chi l'ha creato (`owner` nella tabella `matches`)
- Solo il proprietario può sovrascriverlo: se un altro utente carica quel tabellino e lo salva, il server non tocca l'originale ma crea automaticamente una **copia** con un nuovo id, di cui l'utente diventa proprietario
- L'app avvisa quando succede ("salvato come copia") e l'elenco partite (**Partita → Apri dal server**) mostra per ognuna: proprietario (sempre, non solo per le partite non tue), data di creazione, data di modifica (se diversa da quella di creazione) e l'etichetta **"copia"** quando è nata così
- Ogni copia ricorda solo il suo genitore immediato (`forked_from`), non necessariamente l'originale: la copia di una copia punta alla copia, non risale da sola alla partita di partenza. Nell'elenco entrambe compaiono comunque, quindi restano distinguibili
- Eccezione: i tabellini salvati prima di questa funzione, e quello di prova installato da `demo.php`, non hanno un proprietario e restano modificabili da chiunque (`owner` si assegna solo quando una riga viene creata, mai in un aggiornamento)

---

## Uso durante la partita

### Prima del calcio d'inizio

Nella scheda **Partita**:

1. Compila luogo, data, campionato, giornata e ufficiali di gara.
2. Per ogni squadra inserisci il nome ufficiale (quello richiesto dalla FIR, es. "Rugby Viadana 1970" e non "Viadana"), l'allenatore e i giocatori.

Per fare prima puoi incollare la formazione come testo, una riga per giocatore:

```
1 Genovese
2 Pelli
3 Gentile
4 Midena (Cap.)
...
23 Vanzella
```

I numeri 1–15 sono i titolari, 16–23 i giocatori a disposizione. Scrivi i cognomi esattamente come devono comparire nel tabellino (es. "Bustos G.").

### Durante il gioco

Nella scheda **Live**:

1. Premi ▶ al fischio d'inizio. Tocca il minuto nel tabellone per impostarlo a mano o azzerarlo.
2. Seleziona la squadra, poi l'azione, poi il giocatore.
3. A fine primo tempo premi **Fine 1° tempo**: il cronometro riparte da 1’ e il 2° tempo inizia alla ripresa del gioco.

Tutto viene salvato sul dispositivo a ogni tocco: un ricaricamento o l'assenza di rete non fanno perdere nulla.

### A fine partita

1. Controlla la scheda **Eventi** e correggi eventuali errori con il tasto **Modifica** (minuto, tempo e giocatore in un solo passaggio).
2. Compila note, spettatori, punti in classifica e Player of the Match.
3. Nella scheda **Tabellino** copia il testo o scarica il `.doc`.
4. Premi **Salva sul server** nella scheda Partita.

---

## Come vengono gestiti i minuti

La FIR chiede il **minutaggio progressivo**: si scrive 55’ e non 15’ st. Seguire però il tempo in modo continuo crea un problema: se il primo tempo ha 5 minuti di recupero, il 45’ del primo tempo si sovrappone ai primi minuti del secondo.

Per questo l'app separa l'inserimento dall'output:

| | Primo tempo | Secondo tempo |
|---|---|---|
| **Inserimento** | 1’, 2’ … 40’, 43’ di recupero | riparte da 1’ |
| **Ordinamento** | prima tutti gli eventi del 1° tempo | poi quelli del 2° |
| **Tabellino FIR** | 43’ resta 43’ | 15’ st diventa 55’ |

In questo modo un evento al 43’ di recupero compare sempre prima di uno al 2’ del secondo tempo, anche se nel tabellino il secondo diventa 42’.

Nel pannello di selezione del giocatore c'è un interruttore **pt / st**: permette di registrare un evento nel tempo giusto anche se lo si inserisce in ritardo, per esempio durante l'intervallo.

### Il cronometro

- Viene salvato sul dispositivo e riprende correttamente dopo un ricaricamento della pagina.
- Sul server viene salvato **fermo** al minuto corrente; una partita riaperta dal server parte sempre in pausa.
- Se un tempo risulta più lungo di 60 minuti (cronometro dimenticato acceso), viene fermato automaticamente al 40’ con un avviso.

---

## Sostituzioni e casi particolari

L'app tiene traccia di **chi occupa ogni ruolo da 1 a 15** in ogni momento, e da questo costruisce le annotazioni tra parentesi del tabellino.

| Situazione | Come si registra | Risultato nel tabellino |
|---|---|---|
| Sostituzione normale | Sostituzione: esce 3, entra 17 | `Ravalle (28’ De Marchi An.)` |
| Sostituzione temporanea (sangue, HIA) | Sost. temporanea al 8’, poi "Rientra ora" al 15’ | `Favaro (8’-15’ Mbandà)` |
| Rientro di un giocatore già sostituito | Esce 1 per il 3 (che era uscito prima) | `Boccalon (56’ Ravalle)` |
| Più cambi nello stesso ruolo | In sequenza | `Favaro (44’ Minto, 70’ Favaro)` |
| Giallo a un pilone con mischia da giocare | Giallo al pilone + sost. temporanea di un altro avanti, rientro a fine sanzione | `Odiete (35’-45’ Ferrari)` |
| Temporanea che diventa definitiva | Non premere "Rientra ora" | `Odiete (35’ Ferrari)` |
| Il sostituto temporaneo viene a sua volta sostituito | Sostituzione con esce = il sostituto | annotazioni in sequenza sullo stesso ruolo |

Un rientro può avvenire anche nel tempo successivo (giallo al 35’ del primo tempo, rientro al 5’ del secondo): nel tabellino compare come `35’-45’`.

Le sostituzioni vanno registrate con attenzione: se si indica come "esce" un giocatore che in quel momento non è in campo, il cambio non può essere attribuito a nessun ruolo e non compare nel tabellino. La griglia aiuta mostrando attenuati i giocatori non pertinenti.

---

## Il tabellino generato

### Contenuto

- Luogo, data, campionato e giornata
- Risultato finale con parziale del primo tempo
- Marcatori divisi per tempo con punteggio progressivo: `57’ m. Pizarro tr. Bustos G. (19-18)`
- Formazioni con i reparti separati da `;`: estremo; tre quarti; mediani; terza linea; seconda linea; prima linea
- Giocatori a disposizione e allenatore
- Ufficiali di gara (le righe vuote non vengono stampate)
- Cartellini con colore, raggruppati quando cadono nello stesso minuto: `33’ giallo Krause (MPS Viadana) e Anouer (Femi-CZ Rovigo)`
- Calciatori con centrati/totali, **drop esclusi**, trasformazioni e piazzati sommati: `Bustos G. (Femi-CZ Rovigo) 6/8`
- Note con spettatori, punti in classifica, Player of the Match

Abbreviazioni usate: `m.` meta, `tr.` trasformazione, `cp.` calcio piazzato, `drop`, `m. tecnica`.

### Formato: modello Serie A Elite 2026

Il tabellino segue il modello FIR della Serie A Elite 2026. Esempio con la partita di prova (in **grassetto** le etichette e le due righe di apertura, che sono **_grassetto corsivo_**):

> **_Rovigo – Stadio “Mario Battaglini” – sabato 23 maggio 2015_**
> **_Eccellenza, II giornata_**
> Femi-CZ Rovigo vs MPS Viadana 22-18 (9-9)
>
> **Marcatori: p.t.** 9’ cp. Bustos G. (3-0); 18’ cp. Bustos G. (6-0); … 43’ cp. Law (9-9)
> **s.t.** 45’ cp. Bustos G. (12-9); … 57’ m. Pizarro tr. Bustos G. (19-18); 66’ cp. Bustos G. (22-18)
>
> **Femi-CZ Rovigo:** Basson; Calanchini, Pedrazzi, Pizarro, Pratichetti A.; Bustos G., Legora; Abadie, Burman, Anouer; Barion (52’ Tumiati), Reato (Cap.); Ravalle (28’ De Marchi An.), Mahoney (81’ Damiano), Boccalon (56’ Ravalle)
> **a disposizione:** Damiano, De Marchi An., Tumiati
> **all.:** Coppo
>
> **Arb.:** De Santis
> **AA1:** Nome **AA2:** Nome
> **quarto uomo:** Nome
> **TMO:** Nome
> **Cartellini:** 22’ giallo Cox (MPS Viadana); 33’ giallo Krause (MPS Viadana) e Anouer (Femi-CZ Rovigo)
> **Calciatori:** Bustos G. (Femi-CZ Rovigo) 6/8; Basson (Femi-CZ Rovigo) 0/1; Law (MPS Viadana) 5/5
> **Note:** giornata afosa, campo in buone condizioni, spettatori circa 4750.
> **Punti conquistati in classifica:** Femi-CZ Rovigo 4; MPS Viadana 1
> **Player of the Match:** Persico A. (Viadana)

Regole applicate:

| Elemento | Formato |
|---|---|
| Luogo e data | **_grassetto corsivo_**, separati da ` – ` |
| Campionato e giornata | **_grassetto corsivo_**, separati da virgola, sulla stessa riga |
| Risultato | `Casa vs Ospiti` con il parziale del primo tempo tra parentesi, normale |
| Marcatori | **Marcatori: p.t.** sulla prima riga, **s.t.** a capo; minuti progressivi |
| Formazioni | **nome squadra:** in grassetto, poi reparti separati da `;`, capitano `(Cap.)` |
| Tutte le altre etichette (a disposizione:, all.:, Arb.:, AA1:/AA2:, quarto uomo:, TMO:, Cartellini:, Calciatori:, Note:, Punti conquistati in classifica:, dicitura premio) | **grassetto**, il contenuto resta normale |

Nel luogo conviene scrivere città e stadio separati dal trattino, come nel modello: `Mogliano Veneto – Stadio “Maurizio Quaggia”`.

### Esportazione

- **Copia con grassetti e corsivi**: mette negli appunti la versione con i grassetti e i corsivi del modello, da incollare in Word, Outlook o Gmail. Richiede HTTPS o `localhost`; altrimenti usa un metodo alternativo basato sulla selezione.
- **Copia solo testo**: testo semplice senza formattazione.
- **Scarica .doc**: file HTML con estensione `.doc`. Word lo apre mantenendo la formattazione, ma può avvisare che il formato non corrisponde all'estensione: basta confermare.

### Esportare e importare una partita in JSON

Nella scheda **Partita → Archivio**:

- **Esporta JSON**: scarica un file `.json` con lo stato completo della partita aperta (eventi, formazioni, dati di contorno) — un backup, o un file da far avere a qualcun altro.
- **Importa JSON**: carica un file `.json` esportato così (anche da un'altra installazione) al posto della partita sul dispositivo. Riceve sempre un id nuovo, quindi non sovrascrive nulla sul server finché non si preme **Salva sul server**.

Utile anche per recuperare partite da un'installazione precedente: basta estrarre il contenuto della colonna `data` della riga voluta dal vecchio `data/tabellini.sqlite` e salvarlo in un file `.json`.

---

## Struttura dei file

```
tabellino/
├── index.php             App (interfaccia e logica, richiede login)
├── api.php               Salvataggio e caricamento partite (JSON)
├── db.php                Connessione al database e migrazioni dello schema
├── auth.php              Utenti, sessioni, CSRF, limite tentativi
├── login.php             Accesso e creazione dell'amministratore al primo avvio
├── utenti.php            Gestione utenti e cambio password
├── logout.php            Uscita
├── demo.php              Dati della partita di prova, usati dal primo avvio e dal ripristino
├── ripristina_demo.php   Ripristino della partita di prova (da cancellare dopo l'uso)
├── .htaccess             Blocca file di database/dati, deploy.ini, le cartelle di git, e la directory listing
└── data/                 Creata in automatico, NON va nel repository
    ├── .htaccess         Creato in automatico, nega ogni accesso HTTP
    ├── users.json        Utenti e hash delle password
    ├── tabellini.sqlite  Database
    ├── tabellini.sqlite-wal / -shm   File temporanei di SQLite (modalità WAL)
    ├── sessions/         Sessioni di login
    └── login_fails.json  Registro dei tentativi errati
```

`.gitignore` consigliato:

```gitignore
data/*
!data/.htaccess
```

---

## API

Tutte le chiamate richiedono una sessione autenticata; senza, la risposta è `401 {"ok":false,"error":"Accesso richiesto"}`.

| Metodo | Endpoint | Descrizione | Risposta |
|---|---|---|---|
| GET | `api.php?action=list` | Elenco partite (max 200, più recenti prima) | `{"ok":true,"items":[{"id","title","match_date","owner","updated_at","mine"}]}` |
| GET | `api.php?action=load&id=…` | Stato completo di una partita | `{"ok":true,"data":{…},"owner":"…","mine":true}` |
| POST | `api.php?action=save` | Salva o aggiorna una partita; corpo JSON dello stato | `{"ok":true,"id":"…","forked":false}` |
| GET | `api.php?action=ping` | Mantiene viva la sessione (l'app lo chiama ogni 10 minuti) | `{"ok":true}` |

Vincoli: `id` alfanumerico fino a 40 caratteri, corpo massimo 2 MB. Le chiamate GET dall'app aggiungono un parametro sempre diverso per evitare risposte in cache da proxy o nginx.

I campi `owner`/`mine`/`forked` riguardano il permesso di modifica: vedi [Proprietà dei tabellini](#proprietà-dei-tabellini) in Sicurezza.

---

## Modello dei dati

### Tabella SQLite

```sql
CREATE TABLE matches (
    id          TEXT PRIMARY KEY,
    title       TEXT NOT NULL,     -- "Casa v Ospiti (AAAA-MM-GG)"
    match_date  TEXT,
    data        TEXT NOT NULL,     -- stato completo della partita in JSON
    owner       TEXT NOT NULL DEFAULT '',  -- utente che l'ha creata; '' = nessuno (libera per tutti)
    created_at  TEXT NOT NULL DEFAULT '',  -- mai toccata dopo la creazione della riga
    updated_at  TEXT NOT NULL,
    forked_from TEXT NOT NULL DEFAULT ''   -- id del genitore immediato, solo se nata da una copia
);
```

Lo schema è gestito da `db.php`: le migrazioni sono numerate e applicate una sola volta, tracciate con `PRAGMA user_version` del file `.sqlite` stesso (non serve una tabella a parte). Per aggiungere una colonna o una tabella si accoda una voce a `MIGRATIONS`, senza mai cancellare o ricreare il database esistente.

Lo stato è salvato come un unico JSON: aggiungere campi non richiede di modificare la tabella.

### Stato della partita (semplificato)

```json
{
  "id": "mmu480pj4",
  "minuteMode": "half",
  "info": {
    "luogo": "Rovigo – Stadio “Mario Battaglini”",
    "data": "2015-05-23",
    "campionato": "Eccellenza",
    "giornata": "II giornata",
    "arbitro": "De Santis", "aa1": "", "aa2": "", "quarto": "", "tmo": "",
    "spettatori": "4750", "note": "…",
    "puntiH": "4", "puntiA": "1",
    "potmLabel": "Player of the Match", "potm": "Persico A. (Viadana)"
  },
  "teams": {
    "h": { "name": "Femi-CZ Rovigo", "coach": "Coppo",
           "players": { "1": { "name": "Boccalon", "cap": false }, "…": {} } },
    "a": { "name": "MPS Viadana", "coach": "Bernini", "players": {} }
  },
  "events": [
    { "id": 1,  "t": "pen",  "team": "h", "half": 1, "min": 9,  "n": 10, "ok": true },
    { "id": 22, "t": "try",  "team": "h", "half": 2, "min": 17, "n": 12 },
    { "id": 23, "t": "conv", "team": "h", "half": 2, "min": 17, "n": 10, "ok": true, "link": 22 },
    { "id": 8,  "t": "sub",  "team": "h", "half": 1, "min": 28, "n": 3,  "n2": 17 },
    { "id": 30, "t": "tsub", "team": "h", "half": 1, "min": 35, "n": 3,  "n2": 17, "end": 5, "endHalf": 2 }
  ],
  "clock": { "half": 2, "running": false, "startTs": 0, "accMs": 2400000 },
  "seq": 31
}
```

| Campo evento | Significato |
|---|---|
| `t` | `try` meta, `ptry` meta tecnica, `conv` trasformazione, `pen` calcio piazzato, `drop`, `yc` giallo, `rc` rosso, `sub` sostituzione, `tsub` sostituzione temporanea |
| `team` | `h` casa, `a` ospiti |
| `half`, `min` | tempo (1 o 2) e minuto **relativo al tempo** |
| `n` | maglia del giocatore (per le sostituzioni: chi esce) |
| `n2` | chi entra (solo sostituzioni) |
| `ok` | calcio centrato o sbagliato |
| `link` | per una trasformazione, l'`id` della meta a cui si riferisce |
| `end`, `endHalf` | minuto e tempo di rientro di una sostituzione temporanea |

Le partite salvate prima della v1.03 (minuti progressivi) vengono convertite automaticamente quando si riaprono; il database si aggiorna al successivo salvataggio.

### Preferenze del dispositivo

L'inversione dei lati (⇄) è salvata solo nel browser, nella chiave `tabellino_ui_v1`, e non va sul server: due persone ai lati opposti del campo possono avere ciascuna la propria disposizione.

---

## Partita di prova

La partita di prova può essere caricata al primo avvio, lasciando la spunta nella schermata "Benvenuto". In qualsiasi altro momento si usa `ripristina_demo.php`, che scrive nel database la stessa partita Femi-CZ Rovigo v MPS Viadana del 23/05/2015 tratta dal fac-simile FIR: 22-18 (9-9), 30 eventi, sostituzioni, cartellini e calciatori identici al documento. Il tabellino esce nel formato Serie A Elite 2026. Serve per provare l'app o per ripristinarla dopo dei test.

1. Apri `tabellino/ripristina_demo.php` (richiede login).
2. Scegli la partita da sovrascrivere oppure "Crea una nuova copia".
3. Nell'app vai in **Partita → Apri dal server**: il browser conserva la versione precedente finché non la ricarichi.
4. Cancella il file dal server quando hai finito.

---

## Limiti noti

- **Punti in classifica** da inserire a mano: le regole di bonus cambiano tra campionati e stagioni.
- **Primo avvio aperto a tutti**: finché l'amministratore non è stato creato, chiunque conosca l'indirizzo può crearlo. Va fatto subito dopo il caricamento dei file.
- **Limite tentativi per IP**: dietro un proxy o un CDN `REMOTE_ADDR` può essere l'indirizzo del proxy, e il blocco colpirebbe tutti gli utenti insieme.
- **Durata della sessione**: se il PHP del server non consente di cambiare `session.save_path` o `gc_maxlifetime` da codice, la sessione può durare meno delle 12 ore previste. I dati della partita restano comunque sul dispositivo.
- **Modifiche in parallelo**: se due dispositivi salvano la stessa partita, vince l'ultimo salvataggio. Non c'è unione automatica.
- **Sincronizzazione**: il salvataggio sul server è manuale, non avviene a ogni evento.
- **File `.doc`**: è HTML, non un vero `.docx`; Word può mostrare un avviso all'apertura.
- **Copia formattata**: il risultato dipende dal programma in cui si incolla.

---

## Cronologia versioni

| Versione | Novità |
|---|---|
| **v1.15** | Su hosting con una versione di SQLite molto datata (`pdo_sqlite` 3.7.x, prima del 2018), il salvataggio falliva sempre: la query usava una sintassi (`ON CONFLICT ... DO UPDATE`) introdotta solo nella SQLite 3.24 |
| **v1.14** | Su telefono, toccare un bottone qualsiasi non dava alcun segnale visivo (il flash nativo è disattivato apposta, ma mancava un sostituto); e la riga "Importa JSON / Esporta JSON / Nuova partita / Apri dal server" in fondo alla scheda Partita a volte non rispondeva al tocco, perché il riquadro invisibile del menu utente (tasto **+**) ci restava sopra anche da chiuso |
| **v1.13** | Al primo avvio, prima di far creare l'amministratore, l'app controlla di poter scrivere in `data/` e di poter salvare un tabellino nel database: se l'hosting non lo permette (permessi, `pdo_sqlite` mancante) lo dice subito con un messaggio comprensibile, invece di scoprirlo dopo da un "Salvataggio non riuscito" a partita in corso |
| **v1.12** | L'elenco partite mostra sempre il proprietario (non solo per quelle non tue), la data di creazione oltre a quella di modifica, e un'etichetta "copia" per i tabellini nati da un salvataggio su una partita non tua — prima due partite con lo stesso titolo erano indistinguibili |
| **v1.11** | Compatibilità estesa fino a PHP 7.4 (prima richiedeva 8.1 per un tipo di ritorno `never` in `api.php` e un `mixed` in `auth.php`; `str_contains`/`str_starts_with`, PHP 8.0+, sostituiti con `strpos()`), per chi ha un hosting con una versione di PHP meno recente |
| **v1.10** | Esportazione e importazione delle partite in JSON; tasto "Modifica" unico sugli eventi (minuto, tempo e giocatore/i in un solo passaggio, incluso il rientro delle sostituzioni temporanee); grassetti sulle etichette del tabellino oltre ai corsivi già previsti dal modello FIR; menu utente ("Utenti e password", "Esci") spostato in un tasto flottante raggiungibile da ogni scheda; schema del database gestito con migrazioni numerate in `db.php` |
| **v1.09** | Ogni tabellino appartiene a chi l'ha creato: se un altro utente lo salva, il server crea automaticamente una copia con nuovo id invece di sovrascrivere l'originale |
| **v1.08** | Partita di prova proposta al primo avvio con una spunta; dati della demo spostati in `demo.php`, condiviso con la pagina di ripristino |
| **v1.07** | Un solo formato di tabellino, il modello FIR Serie A Elite 2026: eliminato lo schema “Fac-simile FIR” e la relativa scelta; le partite salvate con l'altro schema vengono mostrate nel nuovo formato |
| **v1.06** | Primo avvio guidato per creare l'amministratore dal browser; pagina "Utenti e password" per aggiungere utenti, reimpostare ed eliminare; utenti in `data/users.json`; creazione automatica di `data/.htaccess`; importazione dell'hash dalle versioni precedenti |
| **v1.05** | Cronometro salvato fermo sul server; protezione contro cronometri rimasti accesi; impostazione diretta di minuto e tempo toccando il tabellone |
| **v1.04** | Tasto ⇄ per invertire i lati delle squadre, preferenza salvata per dispositivo |
| **v1.03** | Minuti relativi al tempo con conversione progressiva nel tabellino; ordinamento per tempo e minuto; AA1, AA2, quarto uomo, TMO; Player of the Match con dicitura; due schemi di formattazione con grassetti e corsivi; copia formattata e download `.doc`; riga "a disposizione" |
| **v1.02** | Login con utenti e password, sessioni dedicate, CSRF, blocco tentativi, API protetta |
| **v1.01** | Parametro anti-cache sulle chiamate GET |
| **v1.00** | Prima versione: registrazione eventi, formazioni, tabellino testuale, salvataggio su SQLite |

---

## Riferimenti

Il formato del tabellino segue il documento della Federazione Italiana Rugby inviato alle società (modello Serie A Elite 2026). In caso di dubbi sul formato vale sempre l'indicazione più recente della FIR.
