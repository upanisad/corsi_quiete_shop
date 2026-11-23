#!/usr/bin/env python3
"""Legge dati/catalogo.csv (dal dataset del caso) e produce dati/prodotti.json
con tutto quello che serve al negozio: prezzi IVA inclusa, categorie, classi di
spedizione, testi. Rilanciare se cambia il catalogo."""
import csv, json, pathlib

QUI = pathlib.Path(__file__).resolve().parent.parent
CAT = {"vino": "Vini", "formaggi": "Formaggi", "yogurt": "Yogurt",
       "dolci": "Dolci", "confezioni": "Confezioni regalo"}
IVA_CLASSE = {"22": "", "10": "iva-10", "4": "iva-4"}   # "" = aliquota standard 22%

# (descrizione breve, descrizione lunga). Solo fatti del catalogo: niente premi, punteggi o claim inventati.
TESTI = {
 "V01": ("Il Brut della casa: secco, fresco, il nostro più venduto.",
         "Valdobbiadene Prosecco Superiore DOCG nella versione Brut, secca e fresca. È il vino che ci rappresenta di più e quello che i clienti riordinano più spesso. Bottiglia da 0,75 l."),
 "V02": ("Extra Dry dal gusto più morbido del Brut.",
         "Valdobbiadene Prosecco Superiore DOCG Extra Dry, leggermente più morbido del Brut. Adatto all'aperitivo e ai dolci non troppo zuccherati. Bottiglia da 0,75 l."),
 "V03": ("Extra Brut dai vigneti più ripidi. Quantità limitata.",
         "Valdobbiadene DOCG Extra Brut dalle Rive Alte, i nostri vigneti più ripidi. Molto secco e delicato. Se ne produce poco: quando finisce, finisce. Bottiglia da 0,75 l."),
 "V04": ("Rifermentato in bottiglia, con il fondo.",
         "Valdobbiadene DOCG Sui Lieviti, rifermentato in bottiglia: può presentare un leggero fondo naturale. Prima di servire si può capovolgere piano la bottiglia o lasciar depositare. Bottiglia da 0,75 l."),
 "V05": ("Il Brut \"Cime\" nel formato Magnum, forte a Natale.",
         "Il Valdobbiadene Prosecco Superiore DOCG Brut \"Cime\" in bottiglia Magnum da 1,5 litri: pensato per le tavole numerose e per i regali. Va a ruba a dicembre."),
 "V06": ("Prosecco frizzante con il tappo a spago.",
         "Prosecco DOC Treviso Frizzante, quello di tutti i giorni, chiuso con il tradizionale tappo a spago. Bottiglia da 0,75 l."),
 "V07": ("Un bianco fermo da un vitigno locale.",
         "Verdiso, Colli Trevigiani IGT: un vitigno della zona, bianco fermo e fresco. Bottiglia da 0,75 l."),
 "V08": ("Rosso fermo da Merlot e Cabernet.",
         "Rosso \"Sassi\", Colli Trevigiani IGT, da uve Merlot e Cabernet. Un rosso fermo per la tavola di tutti i giorni. Bottiglia da 0,75 l."),
 "V09": ("Vino dolce da uve appassite. Quantità limitata.",
         "Passito \"Sera\", Colli Trevigiani IGT. Vino dolce da dessert, in bottiglia piccola da 0,375 l. Se ne produce poco."),
 "V10": ("Prosecco rosé Brut millesimato.",
         "Prosecco DOC Rosé Brut Millesimato, rosato e secco, con l'annata in etichetta. Bottiglia da 0,75 l."),
 "F01": ("Formaggio latteria giovane, 30 giorni di stagionatura.",
         "Latteria fresco: formaggio dal latte delle nostre vacche, stagionato circa 30 giorni, morbido e delicato. Trancio da circa 300 g. Si conserva fino a 45 giorni dalla produzione. Va tenuto in frigorifero."),
 "F02": ("Formaggio latteria mezzano, 4 mesi di stagionatura.",
         "Latteria mezzano: stagionato circa 4 mesi, più saporito del fresco ma ancora tenero. Trancio da circa 300 g. Si conserva fino a 90 giorni dalla produzione."),
 "F03": ("Formaggio latteria vecchio, 12 mesi di stagionatura.",
         "Latteria vecchio: stagionato 12 mesi, pasta compatta e sapore deciso. Trancio da circa 300 g. Si conserva fino a 180 giorni dalla produzione."),
 "F04": ("Formaggio affinato nelle vinacce, l'Ubriaco al Prosecco.",
         "L'Ubriaco al Prosecco è un formaggio affinato nelle vinacce: la crosta prende il colore dell'uva. Trancio da circa 300 g. Si conserva fino a 120 giorni dalla produzione."),
 "F05": ("Caciotta fresca con erbe aromatiche.",
         "Caciotta alle erbe: forma da circa 400 g, fresca, con erbe aromatiche nella pasta. Si conserva fino a 30 giorni dalla produzione. Va tenuta in frigorifero."),
 "F06": ("Ricotta freschissima. Solo ritiro in cantina.",
         "Ricotta fresca da 250 g, fatta con il latte del giorno. Dura solo 6 giorni, per questo non la spediamo: si ritira in cantina. Va tenuta in frigorifero."),
 "Y01": ("Yogurt bianco intero in vasetto da 150 g. Confezione da 6 vasetti.",
         "Yogurt bianco intero del nostro caseificio, in vasetto da 150 g. Si vende a confezioni da 6 vasetti. Si conserva 21 giorni dalla produzione. Spedito solo in refrigerato e non da giugno ad agosto."),
 "Y02": ("Yogurt ai frutti di bosco, vasetto da 150 g. Confezione da 6 vasetti.",
         "Yogurt ai frutti di bosco del nostro caseificio, in vasetto da 150 g. Si vende a confezioni da 6 vasetti. Si conserva 21 giorni dalla produzione. Spedito solo in refrigerato e non da giugno ad agosto."),
 "Y03": ("Yogurt bianco intero in vasetto da 500 g. Confezione da 2 vasetti.",
         "Yogurt bianco intero del nostro caseificio, in vasetto da 500 g. Si vende a confezioni da 2 vasetti. Si conserva 21 giorni dalla produzione. Spedito solo in refrigerato e non da giugno ad agosto."),
 "Y04": ("Yogurt alla pesca, vasetto da 150 g. Confezione da 6 vasetti.",
         "Yogurt alla pesca del nostro caseificio, in vasetto da 150 g. Si vende a confezioni da 6 vasetti. Si conserva 21 giorni dalla produzione. Spedito solo in refrigerato e non da giugno ad agosto."),
 "D01": ("Biscotti di mais e uvetta della tradizione veneta.",
         "Gli zaeti sono biscotti di farina di mais e uvetta, tipici del Veneto. Confezione da 250 g. Si conservano 90 giorni."),
 "D02": ("Biscotti al Prosecco, ottimi con un calice.",
         "Biscotti preparati con il nostro Prosecco. Confezione da 200 g. Si conservano 90 giorni."),
 "D03": ("Crostata fresca alla confettura.",
         "Crostata alla confettura, di pasta frolla, da 450 g. Si conserva 20 giorni dalla produzione."),
 "D04": ("Pinza veneta, dolce di dicembre e gennaio.",
         "La pinza veneta è il dolce delle feste d'inverno. Da 500 g. Si prepara solo a dicembre e gennaio e si conserva 15 giorni."),
 "D05": ("Focaccia veneta di Pasqua, solo marzo e aprile.",
         "La focaccia veneta di Pasqua, da 750 g, si prepara solo a marzo e aprile. Si conserva 30 giorni."),
 "D06": ("Panettone al passito, solo novembre e dicembre.",
         "Panettone da 750 g con il nostro passito. Si prepara solo a novembre e dicembre e si conserva 45 giorni."),
 "C01": ("Due bottiglie per un brindisi: Brut e Extra Dry.",
         "Confezione regalo \"Brindisi\": una bottiglia di Valdobbiadene Prosecco Superiore DOCG Brut \"Cime\" e una di Extra Dry \"Quiete\"."),
 "C02": ("Un vino, due formaggi e dei biscotti per una tavola di festa.",
         "Confezione regalo \"Tavola delle Cime\": Valdobbiadene DOCG Extra Brut \"Rive Alte\", Latteria vecchio, Ubriaco al Prosecco e Biscotti al Prosecco. Contiene formaggi: si spedisce solo in Italia."),
 "C03": ("Il regalo di Natale: spumante, passito, panettone e zaeti.",
         "Confezione regalo \"Natale in Quiete\": Brut \"Cime\", Passito \"Sera\", Panettone al passito e Zaeti. Disponibile solo a novembre e dicembre."),
}
# errore piantato C02 (versione sporca): nome sbagliato nella descrizione
C02_SPORCA = ("Extra Brut \"Rive Alte\"", "Brut \"Cime\"")

def num(s):
    return float(s.replace(",", ".")) if s else None

def main():
    out = []
    with open(QUI / "dati" / "catalogo.csv", encoding="utf-8-sig") as f:
        for r in csv.DictReader(f, delimiter=";"):
            cod = r["codice"]
            prezzo = num(r["prezzo_sito"])
            nome = r["prodotto"]
            confezione_n = None
            if r["categoria"] == "yogurt":              # il sito vende a confezioni
                confezione_n = 2 if "500 g" in nome else 6
                prezzo = round(prezzo * confezione_n, 2)
                base = nome.replace(" 150 g", "").replace(" 500 g", "")
                nome = f"{base}, confezione da {confezione_n} vasetti" + (" da 500 g" if confezione_n == 2 else " da 150 g")
            stagione = [int(x) for x in r["mesi_stagione"].split(",")] if r["mesi_stagione"] else []
            no_sped = [int(x) for x in r["mesi_no_spedizione"].split(",")] if r["mesi_no_spedizione"] else []
            if cod == "F06":
                classe = "solo-ritiro"
            elif cod == "C02":
                classe = "solo-italia"
            elif r["fresco"] == "1":
                classe = "refrigerato"
            else:
                classe = ""
            breve, lunga = TESTI[cod]
            out.append(dict(
                sku=cod, nome=nome, categoria=CAT[r["categoria"]], formato=r["formato"],
                prezzo=prezzo, iva=r["iva"], classe_iva=IVA_CLASSE[r["iva"]],
                classe_spedizione=classe, durata_gg=int(r["durata_gg"]) if r["durata_gg"] else None,
                mesi_stagione=stagione, mesi_no_spedizione=no_sped,
                confezione_n=confezione_n, componenti=r["componenti"].split("+") if r["componenti"] else [],
                breve=breve, lunga=lunga))
    (QUI / "dati" / "prodotti.json").write_text(json.dumps(
        dict(data_negozio="2026-11-30", c02_sporca=C02_SPORCA, prodotti=out),
        ensure_ascii=False, indent=1), encoding="utf-8")
    print(len(out), "prodotti -> dati/prodotti.json")

if __name__ == "__main__":
    main()
