#!/usr/bin/env python3
"""Arricchisce dati/prodotti.json con le schede prodotto della Bibbia.

  python3 genera_dati.py

Legge dati/schede-prodotto.json (copia di dataset/schede-prodotto.json del repo
upanisad/corsi_quiete_caso: la fonte di verità è là) e aggiorna dati/prodotti.json:
descrizioni, peso, dimensioni, attributi, ingredienti, allergeni, valori nutrizionali.
I campi con stato "Mancante" restano nei dati con il loro valore vero e il flag
"mancante": solo la variante sporca li mostra come "Da verificare".
Si può rilanciare quante volte si vuole: non tocca prezzi, IVA, classi, stagioni.
"""
import json, pathlib, sys

QUI = pathlib.Path(__file__).resolve().parent
sys.path.insert(0, str(QUI))

def it(x, nd=1):
    if x is None: return "—"
    s = f"{x:,.{nd}f}".replace(",", "X").replace(".", ",").replace("X", ".")
    return s.rstrip("0").rstrip(",") if "," in s else s

def energia_vino_kcal(alcol, zuccheri_gl):
    return round(alcol * 0.789 * 7 + (zuccheri_gl / 10) * 4)

ANN_REL_TXT = {0: "", -2: "", -3: ""}

# righe dell'anagrafica alimentare che non sono per il cliente (interne all'azienda)
INTERNI = ("Lotto", "Collegamento", "Scarto", "Verifica del peso", "Analisi microbiologica", "Ricetta con le percentuali",
           "Origine", "Solfiti", "Possibili tracce", "Valori nutrizionali", "Vita commerciale", "Consumo", "Spedizione",
           "Alcol residuo", "Zuccheri della confettura", "Umidità", "Resa", "Calo di peso", "Temperatura e umidità",
           "Cura della forma", "Cottura", "Incubazione", "pH", "Burro e uova", "Burro", "Uova",
           "Temperatura di conservazione", "Confezionamento", "Stagione di produzione")

DIM_VINI = {750: (9, 9, 31), 1500: (12, 12, 38), 375: (7, 7, 25)}

def annata_a(data_negozio):
    anno, mese = int(data_negozio[:4]), int(data_negozio[5:7])
    return anno - 1 if mese >= 4 else anno - 2

def vino(v, A):
    s = v["stato"]
    att = []
    def add(nome, valore, chiave):
        att.append({"nome": nome, "valore": valore, "mancante": s.get(chiave) == "Mancante"})
    add("Produttore", v.get("produttore", "Cantina La Quiete nelle Cime"), "produttore")
    add("Denominazione", v["denominazione"], "denominazione")
    if v["menzione"] != "—": add("Menzione geografica", v["menzione"], "menzione")
    add("Tipologia", v["tipologia"], "tipologia")
    add("Annata", str(A + v["annata_rel"]), "annata")
    add("Formato", {750: "750 ml", 1500: "1,5 l (magnum)", 375: "375 ml"}[v["formato_ml"]], "formato")
    add("Alcol", f"{it(v['alcol_vol'])} % vol", "alcol")
    add("Vitigni", ", ".join(f"{n} {p}%" for n, p in v["vitigni"]), "vitigni")
    add("Zuccheri residui", f"{it(v['zuccheri_gl'])} g/l", "zuccheri")
    add("Acidità totale", f"{it(v['acidita_gl'])} g/l", "acidita")
    if v["pressione_bar"] is not None: add("Pressione", f"{it(v['pressione_bar'])} bar", "pressione")
    add("Anidride solforosa totale", f"{v['solfiti_mg_l']} mg/l", "solfiti")
    add("Provenienza delle uve", v["provenienza"], "provenienza")
    add("Metodo", v["metodo"], "metodo")
    add("Chiusura", v["chiusura"], "chiusura")
    add("Temperatura di servizio", v["temperatura"], "temperatura")
    add("Consumo consigliato", v["consumo"], "consumo")
    kcal = energia_vino_kcal(v["alcol_vol"], v["zuccheri_gl"])
    return dict(
        breve=v["descrizione_breve"], lunga=v["descrizione_lunga"], abbinamenti=v["abbinamenti"],
        peso_kg=v["peso_lordo_g"] / 1000, dim_cm=list(DIM_VINI[v["formato_ml"]]),
        attributi=att, ingredienti=v["ingredienti"], allergeni_testo="Contiene solfiti", tracce_testo="",
        nutrizione=dict(titolo="Valore energetico per 100 ml", righe=[["Energia", f"{kcal} kcal / {round(kcal * 4.184)} kJ"]],
                        mancante=s.get("energia") == "Mancante"),
        conservazione="Conservare in luogo fresco, al riparo dalla luce, in posizione orizzontale." if "Spumante" not in v["tipologia"] and "Frizzante" not in v["tipologia"] else "Conservare in luogo fresco e al buio. Servire freddo e aprire con cautela.")

def alimentare(p):
    v = p["valori_100g"]
    att = [{"nome": "Produttore", "valore": p["produttore"], "mancante": False},
           {"nome": "Denominazione di vendita", "valore": p["denominazione_vendita"], "mancante": False},
           {"nome": "Peso netto", "valore": f"{it(p['formato_g'], 0)} g", "mancante": False}]
    for r in p["anagrafica"]:
        if r["campo"].startswith(INTERNI): continue
        att.append({"nome": r["campo"], "valore": r["valore"], "mancante": r["stato"] == "Mancante"})
    if p["durata_gg"]:
        att.append({"nome": "Da consumarsi entro" if p["tipo_scadenza"] == "da consumarsi entro" else "Da consumarsi preferibilmente entro",
                    "valore": f"{p['durata_gg']} giorni dalla produzione", "mancante": False})
    righe = [["Energia", f"{it(v['energia_kcal'], 0)} kcal / {it(v['energia_kj'], 0)} kJ"],
             ["Grassi", f"{it(v['grassi_g'])} g"], ["di cui acidi grassi saturi", f"{it(v['di_cui_saturi_g'])} g"],
             ["Carboidrati", f"{it(v['carboidrati_g'])} g"], ["di cui zuccheri", f"{it(v['di_cui_zuccheri_g'])} g"],
             ["Fibre", f"{it(v['fibre_g'])} g"], ["Proteine", f"{it(v['proteine_g'])} g"], ["Sale", f"{it(v['sale_g'], 2)} g"]]
    return dict(
        breve=p["descrizione_breve"], lunga=p["descrizione_lunga"], abbinamenti=p["abbinamenti"],
        peso_kg=p["peso_lordo_g"] / 1000, dim_cm=None, attributi=att, ingredienti=p["ingredienti"],
        allergeni_testo=", ".join(p["allergeni"]).capitalize() if p["allergeni"] else "Nessuno",
        tracce_testo=", ".join(p["tracce"]), nutrizione=dict(titolo="Valori nutrizionali medi per 100 g", righe=righe, mancante=False),
        conservazione=p["conservazione"])

def confezione(c, comps, idx, nomi):
    allerg = []
    for x in comps:
        for a in idx[x]["allergeni"]:
            if a not in allerg: allerg.append(a)
    tracce = []
    for x in comps:
        for a in idx[x].get("tracce", []):
            if a not in tracce and a not in allerg: tracce.append(a)
    peso = sum(idx[x]["peso_lordo_g"] for x in comps) + c["peso_imballo_g"]
    att = [{"nome": "Contenuto", "valore": "; ".join(f"{nomi[x]}" for x in comps), "mancante": False},
           {"nome": "Imballo", "valore": c["imballo"], "mancante": False}]
    return dict(
        breve=c["descrizione_breve"], lunga=c["descrizione_lunga"], abbinamenti="",
        peso_kg=peso / 1000, dim_cm=None, attributi=att, ingredienti="", allergeni_testo=", ".join(allerg).capitalize(),
        tracce_testo=", ".join(tracce), nutrizione=None, conservazione="")

def main():
    sch = json.loads((QUI / "dati/schede-prodotto.json").read_text(encoding="utf-8"))
    dati = json.loads((QUI / "dati/prodotti.json").read_text(encoding="utf-8"))
    A = annata_a(dati["data_negozio"])
    idx = {p["codice"]: p for p in sch["vini"] + sch["alimentari"]}
    schede = {}
    for v in sch["vini"]: schede[v["codice"]] = vino(v, A)
    for p in sch["alimentari"]: schede[p["codice"]] = alimentare(p)
    nomi = {d["sku"]: d["nome"] for d in dati["prodotti"]}
    for c in sch["confezioni"]:
        comps = next(d["componenti"] for d in dati["prodotti"] if d["sku"] == c["codice"])
        schede[c["codice"]] = confezione(c, comps, idx, nomi)
    n = 0
    for d in dati["prodotti"]:
        s = schede[d["sku"]]
        d.update(s); n += 1
    dati["annata_a"] = A
    (QUI / "dati/prodotti.json").write_text(json.dumps(dati, ensure_ascii=False, indent=1), encoding="utf-8")
    manc = [(d["sku"], a["nome"]) for d in dati["prodotti"] for a in d["attributi"] if a["mancante"]]
    manc += [(d["sku"], d["nutrizione"]["titolo"]) for d in dati["prodotti"] if d.get("nutrizione") and d["nutrizione"]["mancante"]]
    print(f"{n} prodotti aggiornati; annata A = {A}; campi 'Da verificare' nello sporco: {len(manc)}")
    for sku, nome in manc: print("  ", sku, nome)

if __name__ == "__main__":
    main()
