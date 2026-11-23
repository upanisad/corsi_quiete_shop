# Chiave docente — negozio La Quiete "con errori"

Il negozio "sporco" è identico a quello pulito, tranne 6 errori piantati. Non darla ai corsisti.
Come si vede: **Prodotti → Tutti i prodotti** (colonne prezzo, categorie, scorta) e la scheda di ogni prodotto.

| # | Prodotto | Errore piantato | Dove si vede | Correzione |
|---|---|---|---|---|
| 1 | V01 Brut "Cime" | Prezzo **125,00 €** invece di 12,50 € | Elenco prodotti, colonna Prezzo | Prezzo di listino 12,50 |
| 2 | F03 Latteria vecchio | **IVA 22%** invece del 4% | Scheda → Dati prodotto → Generale → Classe di imposta | Classe "IVA 4%" (i formaggi sono al 4%) |
| 3 | F06 Ricotta fresca | **Spedibile** (nessuna classe di spedizione) | Scheda → Dati prodotto → Spedizione → Classe | Classe "Solo ritiro" (la ricotta dura 6 giorni: si ritira in cantina) |
| 4 | D05 Focaccia di Pasqua | **Acquistabile** a novembre | Elenco prodotti, colonna Scorta ("Disponibile") | Scorta "Esaurito" (si prepara solo a marzo e aprile) |
| 5 | V09 Passito "Sera" | Nella categoria **Formaggi** | Elenco prodotti, colonna Categorie | Categoria "Vini" |
| 6 | C02 "Tavola delle Cime" | Descrizione con **Brut "Cime"** al posto dell'Extra Brut "Rive Alte" | Scheda del prodotto, descrizione | Contiene V03 + F03 + F04 + D02 |

## Come verificare che la correzione funziona (carrello di prova)
- Ricotta F06 nel carrello, indirizzo italiano: deve proporre solo "Ritiro in cantina".
- Un formaggio con indirizzo in Austria o Germania: nessuna spedizione disponibile (formaggi e yogurt solo Italia).
- Ordine sopra 89 € con uno yogurt: spedizione gratuita, ma con 6,00 € di supplemento refrigerato.

## Cose che non sono errori (per non farle "correggere")
- Gli yogurt costano 6 (o 2) vasetti insieme: è voluto, il sito li vende a confezioni.
- Pinza veneta e Focaccia di Pasqua risultano esaurite a novembre: sono dolci stagionali.
- Tre ordini hanno stati particolari (rimborsato, annullato, in attesa di bonifico): servono per gli esercizi sugli ordini.
- Il negozio mostra un avviso "negozio di prova": è voluto.
