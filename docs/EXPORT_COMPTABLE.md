# 📊 Export Comptable - Documentation

## Vue d'ensemble

Le module d'export comptable permet aux chauffeurs WayZo d'exporter leurs transactions au format CSV ou PDF pour leur comptabilité.

## Endpoints API

### GET `/api/exports/transactions`
Récupère les transactions avec statistiques pour preview.

**Paramètres query :**
| Paramètre | Type | Description |
|-----------|------|-------------|
| `dateFrom` | string (YYYY-MM-DD) | Date de début |
| `dateTo` | string (YYYY-MM-DD) | Date de fin |
| `statut` | string | pending, completed, cancelled, refunded |
| `type` | string | sent, received |

**Réponse :**
```json
{
  "transactions": [...],
  "stats": {
    "totalTransactions": 42,
    "totalSent": 1500.00,
    "totalReceived": 2300.00,
    "countSent": 15,
    "countReceived": 27,
    "balance": 800.00,
    "countByStatut": {
      "pending": 5,
      "completed": 35,
      "cancelled": 1,
      "refunded": 1
    }
  },
  "filters": {},
  "generatedAt": "2025-01-15T10:30:00+01:00"
}
```

### GET `/api/exports/transactions/csv`
Télécharge l'export CSV des transactions.

**Réponse :** Fichier CSV (Content-Type: text/csv)

**Colonnes CSV :**
- Référence
- Date
- Montant (€)
- Type (Paiement envoyé / Paiement reçu)
- Statut
- Course - Départ
- Course - Arrivée
- Client
- Contrepartie
- Date de complétion

### GET `/api/exports/transactions/pdf`
Télécharge l'export PDF des transactions.

**Réponse :** Fichier PDF (Content-Type: application/pdf)

**Contenu PDF :**
- En-tête avec logo WayZo
- Informations utilisateur
- Filtres appliqués
- Résumé financier (stats)
- Tableau détaillé des transactions

### GET `/api/exports/transactions/stats`
Récupère uniquement les statistiques.

**Réponse :**
```json
{
  "stats": {...},
  "filters": {},
  "generatedAt": "..."
}
```

### GET `/api/exports/transactions/preview`
Génère un aperçu HTML du PDF.

**Réponse :** HTML (Content-Type: text/html)

## Frontend

### Accès
Menu : **WayZo** → **Export Comptable**
URL : `/concepts/exports`

### Fonctionnalités
1. **Filtres** :
   - Période (date de début / fin)
   - Statut de transaction
   - Type (envoyé / reçu)

2. **Statistiques en temps réel** :
   - Nombre total de transactions
   - Total reçu / envoyé
   - Solde net

3. **Aperçu** :
   - Tableau interactif des transactions
   - Tri et recherche

4. **Export** :
   - Bouton CSV (vert)
   - Bouton PDF (rouge)

## Structure des fichiers

### Backend (Symfony)
```
src/
  Service/
    ExportService.php         # Génération CSV/PDF
  Controller/Api/
    ExportController.php      # Endpoints REST
  Repository/
    TransactionRepository.php # Requêtes filtrées

templates/
  exports/
    transactions.html.twig    # Template PDF
```

### Frontend (React)
```
src/
  services/
    ExportService.js          # API calls
  views/exports/
    Exports.jsx               # Page principale
    index.js
    components/
      ExportsHeader.jsx       # Header + boutons
      ExportsStats.jsx        # Cartes statistiques
      ExportsFilters.jsx      # Formulaire filtres
      ExportsTable.jsx        # Tableau transactions
    hooks/
      useExports.js           # SWR hooks
```

## Utilisation

### Exemple d'export avec filtres
```javascript
import { downloadCSV, downloadPDF, apiGetExportTransactions } from '@/services/ExportService'

// Récupérer les données pour preview
const data = await apiGetExportTransactions({
  dateFrom: '2025-01-01',
  dateTo: '2025-01-31',
  statut: 'completed',
  type: 'received'
})

// Télécharger en CSV
downloadCSV({ dateFrom: '2025-01-01', dateTo: '2025-01-31' })

// Télécharger en PDF
downloadPDF({ statut: 'completed' })
```

## Notes techniques

### DomPDF
Le PDF est généré avec DomPDF v3.1 (déjà installé via Composer).

### Encodage CSV
Le fichier CSV utilise :
- Séparateur : `;` (point-virgule, compatible Excel français)
- Encodage : UTF-8 avec BOM

### Sécurité
Tous les endpoints requièrent :
- Authentification JWT
- Rôle USER ou ADMIN
- Les transactions sont filtrées par utilisateur connecté

## Prochaines améliorations possibles
- [ ] Export par période mensuelle/trimestrielle/annuelle
- [ ] Email automatique des exports
- [ ] Historique des exports générés
- [ ] Intégration avec logiciels comptables (format FEC)
