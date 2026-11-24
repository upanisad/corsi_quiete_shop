# Negozio La Quiete su WordPress Playground — guida del docente

Un negozio WooCommerce finto, che gira nel browser. Ogni corsista ha la sua copia, che si perde ricaricando la pagina. Per lavorare su più giorni si esporta lo ZIP.

## Due versioni
- **Pulito**: 29 prodotti corretti, 15 ordini e 15 clienti di novembre 2026 (dal dataset del caso).
- **Con errori**: uguale, con 11 errori piantati (7 sui prodotti, 4 sulle foto del sito) e alcuni campi tecnici «Da verificare». Vedi `chiave-docente-sporco.md`.

## Contenuti del negozio
- Categorie: Vini, Formaggi, Yogurt, Dolci, Confezioni regalo. Prodotti con codice (SKU) V01…C03 del catalogo del caso.
- IVA: 22% (vino, confezioni), 10% (yogurt, dolci), 4% (formaggi).
- Spedizioni: Italia 9,90 €, gratis sopra 89 € (il supplemento refrigerato di 6,00 € resta). Austria e Germania 14,90 € (importo scelto per la demo, non è nel catalogo del caso), solo vino e dolci non refrigerati. Ricotta: solo ritiro in cantina.
- Yogurt venduti a confezioni da 6 vasetti (da 2 per il 500 g).
- Data del negozio: 30/11/2026. Pinza e Focaccia di Pasqua sono fuori stagione.
- Nessuna email parte, nessun pagamento vero: bonifico e contrassegno finti.

## Come si prepara (una volta sola)
1. Crea il repo pubblico `upanisad/corsi_quiete_shop` e carica il contenuto di questa cartella (dati, src, immagini, build-*.json, pulito.json, sporco.json, docente).
2. Apri il link di costruzione e aspetta (1–3 minuti):
   `https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/upanisad/corsi_quiete_shop/main/build-pulito.json`
3. Controlla il negozio (elenco prodotti, un prodotto, il carrello, Ordini, Clienti).
4. Menu di Playground → **Download as .zip** → salva come `snapshot/quiete-pulito.zip` nel repo. Poi lo stesso con `build-sporco.json` → `snapshot/quiete-sporco.zip`.
5. I due link per i corsisti, che usano lo ZIP:
   - Pulito: `https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/upanisad/corsi_quiete_shop/main/pulito.json`
   - Con errori: `https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/upanisad/corsi_quiete_shop/main/sporco.json`
6. Accorcia i link con un redirect su stylesoft.it e fanne un QR.

Se il file ZIP supera i 100 MB, GitHub non lo accetta nel repo normale: dimmelo e troviamo un'altra sede.

## Se cambia qualcosa
- Catalogo o testi: `python3 src/genera_dati.py`, poi `python3 genera_blueprint.py`, poi ricostruire gli ZIP.
- Immagini: sostituire i file `immagini/V01.jpg` … `C03.jpg` (quadrati, circa 100 KB; i nomi devono restare questi), poi ricostruire gli ZIP.
- Regole di spedizione: `src/quiete-regole.php` (in alto l'importo base e la soglia).

## In aula
- Fai partire tutti insieme a inizio lezione: il primo avvio scarica diversi MB.
- Chrome o Edge aggiornati. Prova il link su un PC dell'aula prima del corso.
- Ricorda: ricaricare la pagina cancella le modifiche. Esportare lo ZIP a fine giornata (scheda del corsista).
- Non cambiare mai utente e password dell'amministratore di Playground (il login automatico non funzionerebbe più).

## Limiti noti
- Le email non partono e i pagamenti sono finti: è voluto.
- Playground è più lento di un hosting vero e su PC vecchi si sente.
- Il cron di WordPress gira solo a scheda aperta.
