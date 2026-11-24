# Chiave docente — negozio La Quiete "con errori"

Il negozio "sporco" è identico a quello pulito, tranne 11 errori piantati (7 sui prodotti, 4 sulle foto del sito) e 12 campi tecnici lasciati «Da verificare» (dati mancanti, vedi la sezione sotto). Non darla ai corsisti.
Come si vede: per i prodotti, **Prodotti → Tutti i prodotti** (colonne prezzo, categorie, scorta) e la scheda di ogni prodotto; per le foto, le pagine **Home**, **Chi siamo** e **Agriturismo** e la **Libreria media**.

| # | Prodotto | Errore piantato | Dove si vede | Correzione |
|---|---|---|---|---|
| 1 | V01 Brut "Cime" | Prezzo **125,00 €** invece di 12,50 € | Elenco prodotti, colonna Prezzo | Prezzo di listino 12,50 |
| 2 | F03 Latteria vecchio | **IVA 22%** invece del 4% | Scheda → Dati prodotto → Generale → Classe di imposta | Classe "IVA 4%" (i formaggi sono al 4%) |
| 3 | F06 Ricotta fresca | **Spedibile** (nessuna classe di spedizione) | Scheda → Dati prodotto → Spedizione → Classe | Classe "Solo ritiro" (la ricotta dura 6 giorni: si ritira in cantina) |
| 4 | D05 Focaccia di Pasqua | **Acquistabile** a novembre | Elenco prodotti, colonna Scorta ("Disponibile") | Scorta "Esaurito" (si prepara solo a marzo e aprile) |
| 5 | V09 Passito "Sera" | Nella categoria **Formaggi** | Elenco prodotti, colonna Categorie | Categoria "Vini" |
| 6 | C02 "Tavola delle Cime" | Descrizione con **Brut "Cime"** al posto dell'Extra Brut "Rive Alte" | Scheda del prodotto, descrizione | Contiene V03 + F03 + F04 + D02 |
| 7 | V03 Extra Brut "Rive Alte" | Zuccheri residui **14 g/l** nella scheda tecnica: è un valore da Extra Dry (12–17), non da Extra Brut (fino a 6). La descrizione dice 4 g/l | Scheda → Informazioni aggiuntive (in amministrazione: Dati prodotto → Attributi) | **4 g/l**, coerente con Extra Brut e con la descrizione |

### Errori sulle foto del sito
Le foto (luoghi e persone) sono generate con AI. Nel pulito sono compresse (circa 250 KB), hanno testo alternativo e nome del file parlante.

| # | Dove | Errore piantato | Come si scopre | Correzione |
|---|---|---|---|---|
| 8 | Home, foto di apertura (Borgo Cime dall'alto) | Caricata l'**originale non compresso**: circa 1 MB per una foto larga 1376 px | Libreria media → dettagli del file (dimensione del file); oppure gli strumenti del browser (scheda Rete) o un test di velocità della pagina | Comprimere (JPEG di qualità 80 circa: da 1 MB a circa 250 KB, a occhio non cambia nulla) e sostituirla |
| 9 | Tutte le foto del sito (Home, Chi siamo, Agriturismo, Contatti, Spedizioni e resi) | **Nessun testo alternativo** (l'attributo alt, la descrizione che leggono i lettori di schermo e i motori di ricerca). In più i file hanno nomi muti (`IMG_4100.jpg` …) | Libreria media → campo «Testo alternativo» vuoto; nell'editor, blocco immagine → «Testo alternativo»; un controllo di accessibilità | Una frase che descrive cosa si vede, per ogni foto (vedi i testi del pulito o `dati/immagini-sito.json`); nomi dei file che dicono cosa c'è dentro |
| 10 | Chi siamo, foto di gruppo in cortile | Didascalia **«Il nostro team.»**, ma nella foto ci sono anche **ospiti dell'agriturismo, tra cui un ragazzino**, pubblicati come se fossero dipendenti e senza dire nulla del consenso | Guardare la foto con le schede dei personaggi accanto: la ragazza con la camicetta, il ragazzino e l'anziano seduto non sono nel team | Per le persone riconoscibili serve il consenso (una liberatoria); per un minore lo firmano i genitori. Senza consenso: un'altra foto, oppure tagliare o sfocare. In ogni caso didascalia vera: «Il team, con alcuni ospiti dell'agriturismo che ci hanno dato il permesso di pubblicare la foto.» |
| 11 | Chi siamo, "Le persone": ritratto di Nicola | Sul coperchio del portatile si vede il **logo di un marchio reale** | Guardare le foto del team con attenzione (anche ingrandendo) | Una foto pubblicitaria non deve mostrare marchi di altri come se ci fosse un legame: ritoccare (coprire o sfocare il logo) o cambiare foto. Nel pulito il logo è già tolto |

## Campi tecnici «Da verificare» (dati mancanti)
Nella scheda del prodotto, tab **Informazioni aggiuntive** (e valore energetico in descrizione), questi campi mostrano «Da verificare». Nel negozio pulito hanno il valore giusto, che è anche la correzione attesa (viene dalla scheda prodotto della Bibbia, `bibbia/08-schede-prodotto.md`, dove lo stato è *Mancante*). Non sono errori di valore: sono dati che l'azienda non ha in anagrafica.

| Prodotto | Campo | Valore atteso |
|---|---|---|
| V02 Valdobbiadene Prosecco Superiore DOCG Extra Dry "Quiete" | Vitigni | Glera 92%, Bianchetta trevigiana 8% |
| V02 Valdobbiadene Prosecco Superiore DOCG Extra Dry "Quiete" | Zuccheri residui | 14 g/l |
| V02 Valdobbiadene Prosecco Superiore DOCG Extra Dry "Quiete" | Acidità totale | 5,8 g/l |
| V02 Valdobbiadene Prosecco Superiore DOCG Extra Dry "Quiete" | Valore energetico per 100 ml | 72 kcal / 301 kJ |
| V04 Valdobbiadene DOCG Sui Lieviti "Fondo" | Zuccheri residui | 1,5 g/l |
| V04 Valdobbiadene DOCG Sui Lieviti "Fondo" | Acidità totale | 6,1 g/l |
| V04 Valdobbiadene DOCG Sui Lieviti "Fondo" | Valore energetico per 100 ml | 64 kcal / 268 kJ |
| V09 Passito "Sera" Colli Trevigiani IGT | Vitigni | Verdiso 70%, Glera 30% |
| V09 Passito "Sera" Colli Trevigiani IGT | Zuccheri residui | 110 g/l |
| V09 Passito "Sera" Colli Trevigiani IGT | Acidità totale | 6,4 g/l |
| V09 Passito "Sera" Colli Trevigiani IGT | Valore energetico per 100 ml | 119 kcal / 498 kJ |
| F01 Latteria fresco (30 giorni) | Grassi sul secco | Circa 44% |

## Come verificare che la correzione funziona (carrello di prova)
- Ricotta F06 nel carrello, indirizzo italiano: deve proporre solo "Ritiro in cantina".
- Un formaggio con indirizzo in Austria o Germania: nessuna spedizione disponibile (formaggi e yogurt solo Italia).
- Ordine sopra 89 € con uno yogurt: spedizione gratuita, ma con 6,00 € di supplemento refrigerato.

## Cose che non sono errori (per non farle "correggere")
- Gli yogurt costano 6 (o 2) vasetti insieme: è voluto, il sito li vende a confezioni.
- Pinza veneta e Focaccia di Pasqua risultano esaurite a novembre: sono dolci stagionali.
- Tre ordini hanno stati particolari (rimborsato, annullato, in attesa di bonifico): servono per gli esercizi sugli ordini.
- Il negozio mostra un avviso "negozio di prova": è voluto.
- Le foto del sito non sono perfette (una scritta parziale su una cassa, l'etichetta di una bottiglia leggibile, nella foto di gruppo mancano Dario, Elena e Ivano): è uguale nel pulito, non sono errori piantati.
