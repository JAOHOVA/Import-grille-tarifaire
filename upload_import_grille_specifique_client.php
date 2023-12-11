<?php
/**
 * Methods for contact document
 *
 * @author Nevea
 * @version $Id$
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package NEVEA_ADDONS
 */
include_once("./FDL/freedom_util.php");

function upload_import_grille_specifique_client ( &$action ) {

    $iIGrilleSpecifiqueClientId = GetHttpVars("id");
    
    if(isset($_POST["submit"])) {
        $sCheminDeDestination = "FDL/tmp/"; // Répertoire où on stocke les fichiers téléversés
        $sNomFichierTemporaire = $_FILES["fileToUpload"]["tmp_name"];
        $sNomFichierOrigine = $_FILES["fileToUpload"]["name"];
        $aInfoFichier = pathinfo($sNomFichierOrigine);
        // Génération d'un nom de fichier unique à partir du timestamp 
        $sNomFichierSansExtension = "ImportGrilleTarifaire_".time();
        $sExtensionFichier = strtolower($aInfoFichier['extension']); // Extension du fichier convertie en minuscules
        $sNouveauNom = $sCheminDeDestination . $sNomFichierSansExtension . '_' . $iIGrilleSpecifiqueClientId. '.' . $sExtensionFichier;
        // $sNouveauNom = $sCheminDeDestination . basename($_FILES["fileToUpload"]["name"]);
        $uploadOk = 1;
        
        // Tableau des extensions de fichiers autorisées
        $aExtentionAutorise = array("jpg", "png", "jpeg", "gif", "pdf", "csv", "xlsx", "zip");
    
        // Vérification du type de fichier
        if (!in_array($sExtensionFichier, $aExtentionAutorise)) {
            echo " Seuls les fichiers JPG, JPEG, PNG, GIF, PDF, CSV, ZIP et XLSX sont autorisés.";
            $uploadOk = 0;
        }
    
        // Vérification de la taille du fichier (ici, 5 Mo)
        if ($_FILES["fileToUpload"]["size"] > 5 * 1024 * 1024) {
            echo " Le fichier est trop volumineux. La taille maximale autorisée est de 5 Mo.";
            $uploadOk = 0;
        }
    
        // Vérification si le fichier existe déjà
        if (file_exists($sNouveauNom)) {
            echo " Le fichier existe déjà.";
            $uploadOk = 0;
        }
    
        // Vérification finale si $uploadOk est défini à 0 par une erreur
        if ($uploadOk == 0) {
            echo " Le fichier n'a pas été téléversé."."<br>";

            $sUrl = '?sole=Y&app=AFFAIRE&action=IMPORT_GRILLE_SPECIFIQUE_CLIENT&id='.$iIGrilleSpecifiqueClientId;

            // Génération du lien avec texte dynamique
            echo '<a href="' . $sUrl . '">Cliquez ici pour revenir à l\'accueil</a>';
            
        } else {
            // Si toutes les vérifications sont passées, procéder à l'upload du fichier
            if (move_uploaded_file($sNomFichierTemporaire, $sNouveauNom)) {
                // echo " Le fichier ". htmlspecialchars( basename( $_FILES["fileToUpload"]["name"])). " a été téléversé avec succès.";
                $sUrl = '?app=AFFAIRE&action=IMPORT_GRILLE_SPECIFIQUE_CLIENT&id='.$iIGrilleSpecifiqueClientId.'&fichierImport='.$sNouveauNom;

                // lien avec du texte dynamique
                // $texte_du_lien = 'Cliquez ici pour revenir à l\'accueil';

                // Génération du lien avec texte dynamique
                // echo '<a href="' . $sUrl . '">' . $texte_du_lien . '</a>';
                header("Location:".$sUrl);
            } else {
                echo "Une erreur est survenue lors du téléversement du fichier.";
            }
        }
    }

}

?>
