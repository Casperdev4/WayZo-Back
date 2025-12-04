<?php
/**
 * Script pour créer des chauffeurs de test
 * Exécuter avec: php create_test_chauffeurs.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Connexion à la base de données
$dbUrl = $_ENV['DATABASE_URL'] ?? 'mysql://root:@127.0.0.1:3306/wayzo';
$parsedUrl = parse_url($dbUrl);

$host = $parsedUrl['host'] ?? '127.0.0.1';
$port = $parsedUrl['port'] ?? 3306;
$dbname = ltrim($parsedUrl['path'] ?? '/wayzo', '/');
$user = $parsedUrl['user'] ?? 'root';
$pass = $parsedUrl['pass'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à la base de données réussie\n";
    
    // Mot de passe hashé pour "password123"
    $hashedPassword = password_hash('password123', PASSWORD_BCRYPT);
    
    $chauffeurs = [
        [
            'email' => 'jean.dupont@wayzo.fr',
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'tel' => '0601020304',
            'siret' => '12345678901234',
            'nom_societe' => 'VTC Dupont',
            'kbis' => 'KBIS-001',
            'carte_vtc' => 'VTC-001'
        ],
        [
            'email' => 'marie.martin@wayzo.fr',
            'nom' => 'Martin',
            'prenom' => 'Marie',
            'tel' => '0602030405',
            'siret' => '23456789012345',
            'nom_societe' => 'Martin Transport',
            'kbis' => 'KBIS-002',
            'carte_vtc' => 'VTC-002'
        ],
        [
            'email' => 'pierre.durand@wayzo.fr',
            'nom' => 'Durand',
            'prenom' => 'Pierre',
            'tel' => '0603040506',
            'siret' => '34567890123456',
            'nom_societe' => 'Durand VTC',
            'kbis' => 'KBIS-003',
            'carte_vtc' => 'VTC-003'
        ],
        [
            'email' => 'sophie.bernard@wayzo.fr',
            'nom' => 'Bernard',
            'prenom' => 'Sophie',
            'tel' => '0604050607',
            'siret' => '45678901234567',
            'nom_societe' => 'Sophie Drive',
            'kbis' => 'KBIS-004',
            'carte_vtc' => 'VTC-004'
        ],
        [
            'email' => 'lucas.petit@wayzo.fr',
            'nom' => 'Petit',
            'prenom' => 'Lucas',
            'tel' => '0605060708',
            'siret' => '56789012345678',
            'nom_societe' => 'Petit Transport',
            'kbis' => 'KBIS-005',
            'carte_vtc' => 'VTC-005'
        ]
    ];
    
    $sql = "INSERT INTO chauffeur (email, password, nom, prenom, tel, siret, nom_societe, kbis, carte_vtc, roles) 
            VALUES (:email, :password, :nom, :prenom, :tel, :siret, :nom_societe, :kbis, :carte_vtc, :roles)";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($chauffeurs as $index => $chauffeur) {
        $stmt->execute([
            'email' => $chauffeur['email'],
            'password' => $hashedPassword,
            'nom' => $chauffeur['nom'],
            'prenom' => $chauffeur['prenom'],
            'tel' => $chauffeur['tel'],
            'siret' => $chauffeur['siret'],
            'nom_societe' => $chauffeur['nom_societe'],
            'kbis' => $chauffeur['kbis'],
            'carte_vtc' => $chauffeur['carte_vtc'],
            'roles' => '["ROLE_USER"]'
        ]);
        
        echo "✅ Chauffeur créé: {$chauffeur['prenom']} {$chauffeur['nom']} ({$chauffeur['email']})\n";
    }
    
    echo "\n🎉 5 chauffeurs créés avec succès !\n";
    echo "📧 Mot de passe pour tous: password123\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
