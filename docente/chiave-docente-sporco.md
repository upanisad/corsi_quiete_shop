# Chiave docente — negozio La Quiete "con errori"

Il negozio "sporco" è identico a quello pulito, tranne 6 errori piantati e 12 campi tecnici lasciati «Da verificare» (dati mancanti, vedi la sezione sotto). Non darla ai corsisti.
Come si vede: **Prodotti → Tutti i prodotti** (colonne prezzo, categorie, scorta) e la scheda di ogni prodotto.

| # | Prodotto | Errore piantato | Dove si vede | Correzione |
|---|---|---|---|---|
| 1 | V01 Brut "Cime" | Prezzo **125,00 €** invece di 12,50 € | Elenco prodotti, colonna Prezzo | Prezzo di listino 12,50 |
| 2 | F03 Latteria vecchio | **IVA 22%** invece del 4% | Scheda → Dati prodotto → Generale → Classe di imposta | Classe "IVA 4%" (i formaggi sono al 4%) |
| 3 | F06 Ricotta fresca | **Spedibile** (nessuna classe di spedizione) | Scheda → Dati prodotto → Spedizione → Classe | Classe "Solo ritiro" (la ricotta dura 6 giorni: si ritira in cantina) |
| 4 | D05 Focaccia di Pasqua | **Acquistabile** a novembre | Elenco prodotti, colonna Scorta ("Disponibile") | Scorta "Esaurito" (si prepara solo a marzo e aprile) |
| 5 | V09 Passito "Sera" | Nella categoria **Formaggi** | Elenco prodotti, colonna Categorie | Categoria "Vini" |
| 6 | C02 "Tavola delle Cime" | Descrizione con **Brut "Cime"** al posto dell'Extra Brut "Rive Alte" | Scheda del prodotto, descrizione | Contiene V03 + F03 + F04 + D02 |

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
