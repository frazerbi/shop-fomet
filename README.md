# Shop Fomet

Sito e-commerce di Fomet S.p.A. — WordPress + WooCommerce, storefront costruito con
Elementor. Sostituisce il vecchio negozio PrestaShop.

In questo repo è tracciato **solo il child theme**, in
[`wp-content/themes/hello-theme-child/`](wp-content/themes/hello-theme-child/)
(child di Hello Elementor). Plugin e uploads stanno fuori.

> Documentazione tecnica per chi lavora sul codice (architettura, workflow SCSS,
> design token, override dei widget WooCommerce): [CLAUDE.md](CLAUDE.md).
> Questo README raccoglie le **procedure di configurazione** da eseguire in wp-admin.

## Build degli stili

Il CSS è compilato da SCSS: **non modificare mai `style.css` a mano**.

```bash
cd wp-content/themes/hello-theme-child
npm install        # una volta sola
npm run watch      # sviluppo
npm run build      # produzione (compresso, senza source map)
```

Dopo un `build`, ricordarsi di alzare `Version:` nell'header di `style.scss`:
il numero di versione è letto da lì e usato per il cache busting.

---

## Spedizioni — configurazione

Le tariffe replicano quelle del vecchio PrestaShop: **scaglioni di peso × zona
geografica**. Niente calcolo per distanza, niente scaglioni a valore, nessuna soglia
di spedizione gratuita. L'estrazione completa dal dump, con le prove SQL dietro ogni
numero, è in `backup-old-db/export/shipping.md` (gitignored).

Il codice sta in due file, entrambi richiesti da `functions.php`:

| File | Contiene |
|---|---|
| `woocommerce-shipping.php` | il metodo di spedizione `child_weight_shipping` e i listini |
| `woocommerce-shipping-zones.php` | lo script one-shot che crea le zone |

### Configurazione assistita (consigliata)

Da riga di comando, con WooCommerce attivo:

```bash
wp eval 'child_sync_shipping_zones();'
```

Oppure, senza WP-CLI, aggiungendo `?child_sync_shipping=1` a un qualsiasi URL di
wp-admin (serve il permesso `manage_woocommerce`): l'esito compare come notice.

Lo script crea entrambe le zone nell'ordine corretto, con le province giuste, i due
metodi per zona nell'ordine giusto e **il listino già assegnato** a ciascuno.

È **idempotente per nome**: le zone che trova già esistenti le salta, non le
sovrascrive. Quindi se hai già creato a mano una zona con lo stesso nome, lo script
non farà nulla su quella — vanno cancellate prima da
*WooCommerce → Impostazioni → Spedizione*.

### Configurazione manuale

Se preferisci farlo dall'interfaccia, il risultato da ottenere è questo:

```
Zona 1 — Isole e Calabria
  Regioni: CZ CS KR RC VV · AG CL CT EN ME PA RG SR TP · CA NU OR SS SU
  ├─ Spese di spedizione a peso   → Listino: "Isole e Calabria"
  └─ Ritiro presso la sede        → Ritiro in sede, costo 0

Zona 2 — Italia
  Regioni: Italia (nessuna restrizione di provincia)
  ├─ Spese di spedizione a peso   → Listino: "Italia (continente)"
  └─ Ritiro presso la sede        → Ritiro in sede, costo 0
```

Quattro punti in cui è facile sbagliare:

1. **"Spese di spedizione a peso" NON è "Tariffa unica".** È una voce a sé nel menu
   *Aggiungi metodo di spedizione*, accanto a Tariffa unica / Spedizione gratuita /
   Ritiro in sede. La riconosci perché **non ha un campo "Costo"**: ha invece
   *Titolo*, *Listino* e *Oltre il peso massimo*. Se usi "Tariffa unica" a costo 0,
   il negozio spedisce gratis.
2. **Il Listino va impostato su entrambe le istanze.** Il default è "Italia", quindi
   una zona isole lasciata al default applica le tariffe del continente.
3. **L'ordine delle zone conta.** WooCommerce applica al cliente la **prima** zona che
   fa match. "Isole e Calabria" deve stare **sopra** "Italia", che non avendo
   restrizioni di provincia matcha chiunque. Si riordinano trascinandole.
4. **L'ordine dei metodi dentro la zona** decide quale è preselezionato al checkout:
   tieni la spedizione sopra il ritiro.

### Impostazione fiscale da non toccare

*WooCommerce → Impostazioni → Tasse → Classe fiscale spedizione* deve restare sul
default **"Classe fiscale di spedizione basata sugli articoli del carrello"**.

Il trasporto addebitato dal venditore è una prestazione accessoria (art. 12 DPR
633/72): segue l'aliquota del bene principale, non ne ha una propria. Il catalogo è
misto — 34 prodotti al 4%, 4 gadget al 22%, BIOKELP® al 10% — quindi il codice non
forza nessuna aliquota di proposito, e quell'impostazione è l'unico punto di
controllo. Mettendola su "Standard" si torna al 22% fisso, che sul vecchio shop era
un errore.

### Verifica

Carrello con **2 sacchi da 5 kg** verso una provincia continentale:

| Attesa | Valore |
|---|---|
| Peso rilevato | 10 kg |
| Spedizione | 13,25 € + IVA 4% |
| Metodi offerti | "Spedizione" (preselezionato) e "Ritiro presso la sede" a 0 € |

Ripetendo verso una provincia siciliana la spedizione deve salire a **15,75 €**.
Aggiungendo al carrello un gadget al 22% (Cappellini, Magliette, Pluviometro)
l'IVA sulla spedizione deve passare al 22%.

Il peso calcolato viene salvato come meta della riga spedizione dell'ordine
(`Peso spedizione: 10 kg`), visibile in admin: utile per il confronto con la bolla
del corriere.

### Modificare le tariffe

I listini vivono in `child_shipping_rate_tables()`, dentro
`woocommerce-shipping.php`. Prezzi **IVA esclusa**, pesi in **kg**, `a` è il limite
superiore incluso. Modificare l'array, committare, deployare: le tariffe attive
sono visibili in admin sotto il campo *Listino*, ma non sono modificabili da lì —
è il compromesso scelto per averle versionate in git.

Oltre l'ultimo scaglione (100 kg) il comportamento è configurabile per istanza
(*Oltre il peso massimo*): tariffa "da concordare" a 0 € (default), corriere non
disponibile, oppure applica l'ultimo scaglione.

### Da sistemare prima del go-live

- **4 prodotti hanno peso 0**: Cappellini, Magliette, Pluviometro, Spandiconcime
  (quest'ultimo disattivo). Non contribuiscono al peso del carrello, quindi un
  ordine che li contiene viene sottotariffato.
- **Nessuna regola oltre i 100 kg.** Bastano 21 sacchi da 5 kg. Da decidere col
  cliente se serve una tariffa a bancale invece del "da concordare".
- **Nessuno stile SCSS** per le righe di spedizione in carrello e checkout: quel
  markup non è mai stato renderizzato finora. Servono gli HTML reali dei widget
  Elementor Pro prima di scrivere le regole, come per tutti gli altri widget Woo.
