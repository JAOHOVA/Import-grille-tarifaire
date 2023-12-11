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

function import_grille_specifique_client ( &$action ) {

    // Récupérer les variables présentes dans l'URL ici id
    $idGrilleTarifaireClient = GetHttpVars("id");
    $sFichierImport = GetHttpVars("fichierImport", false);

    // id cacher du côté template
    echo '<input id="idGrilleTarifaireClient" name="idGrilleTarifaireClient" type="text" value="' . $idGrilleTarifaireClient . '" hidden/>';

    if(!$sFichierImport){
        $import = '<form action="?app=AFFAIRE&action=UPLOAD_IMPORT_GRILLE_SPECIFIQUE_CLIENT" method="post" enctype="multipart/form-data">
                    <input type="file" name="fileToUpload" id="fileToUpload">
                    <input type="hidden" name="id" id="idDoc" value="'.$idGrilleTarifaireClient.'">
                    <input type="submit" value="Importer" name="submit">
                </form>';

        $action->lay->Set("UPLOAD", $import);
        $action->lay->Set("validButton", false);

    }
    else{ 

        $action->lay->Set("UPLOAD", "");
        $action->lay->Set("validButton", true);

        $oGrilleSpecifiqueClient = new_Doc("", $idGrilleTarifaireClient);
        // Donnée brut de DB
        $aGrilleSpClient = $oGrilleSpecifiqueClient->getArrayRawValues("gts_t_containt");
        $aChampSousTarif = array_keys($aGrilleSpClient[0]);
        $oFamille = new_Doc("", "GRILLETARIFAIRE");
        foreach($aChampSousTarif as $sChampSousTarif){
            $oAttribut = $oFamille->getAttribute($sChampSousTarif);
            $aEntete[] = $oAttribut->labelText;
            // echo $oAttribut->labelText." (".$oAttribut->visibility.")<br/>";
        }

        // Récupérer le chemin du fichier
        $sCheminFichier = $sFichierImport;//$sFichierImport FDL/tmp/test.csv
        // Lire le contenu du fichier
        $sContenuFichier = file_get_contents($sCheminFichier);
        // Diviser le contenu en lignes
        $slignes = explode("\n", $sContenuFichier);
        // Initialiser un tableau pour stocker les données CSV
        $aDonneeCsv = [];
        // Parcourir chaque ligne et la diviser en valeurs
        foreach ($slignes as $ligne) {
            $aDonnees = str_getcsv($ligne, ';');
            $aDonneeCsv[] = $aDonnees;
        }

        // Récupérer l'en-tête
        $aEnteteCsv = $aDonneeCsv[0];
        // Supprimer la première ligne (en-tête) si elle contient les noms de colonnes
        array_shift($aDonneeCsv);
        // Supprimer la dernière ligne vide 
        array_pop($aDonneeCsv);

        // Afficher le tableau résultant
        $html = "<table class='table'>
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Etat</th>
                            <th>Désignation</th>
                            <th>Référence, si sous tarif</th>
                            <th>Intitulé, si sous tarif</th>
                            <th>Durée</th>
                            <th>Modalité</th>
                            <th>Catégorie</th>
                            <th>Nombre de catégories'</th>
                            <th>Tarif HT remisé</th>
                            <th>% remisé'</th>
                        </tr>
                    </thead>
                    <tbody>";
            foreach($aDonneeCsv as $aDonnee)
            {
                $html .= "<tr>";
                // var_dump($value);
                foreach($aDonnee as $aDonnees){
                    $html .= "<td>".$aDonnees."</td>";
                }
                $html ."</tr>";
            }
        $html .= "</tbody></table>";

        // Création d'un nouveau tableau associatif à partir de $aDonneeCsv
        // Tableau contenant les nouvelles clés
        $aNouvelleCle = ['reference',
                        'choix',
                        'etat',
                        'designation',
                        'reference_si_sous_tarif',
                        'intitulé_si_sous_tarif',
                        'duree',
                        'modalite',
                        'categorie',
                        'nombre_de_categories',
                        'tarif_HT_remise',
                        '%_remise'
                    ];
        
        // Tableau pour stocker le résultat pour changer les clés de $aDonneeCsv
        $aNouvelleDonneeCsv = remplacerCles($aDonneeCsv, $aNouvelleCle);

        // Tableau pour stocker le résultat pour changer les clés de $aEnteteCsv
        $aResultatEntete = remplacerClees($aEnteteCsv, $aNouvelleCle);

        // les libellés csv
        $aColonne = [];
        foreach($aResultatEntete as $sCleEntete=> $sValeurEntete)
        {
            $aCreeColonne = creeColonne($sCleEntete, $sValeurEntete);
            array_push($aColonne, $aCreeColonne);
        }

        // $aNouvelleDonneeCsv contient maintenant les tableaux associatifs avec les clés remplacées

        // Tableau d'origine $aGrilleSpClient DB
        // Tableau modifié $aNouvelleDonneeCsv

        // Tableau pour stocker les éléments trouvés dans les deux tableaux
        $aAffichageChangement = [];
        // Comparaison des valeurs de tableau
        foreach ($aGrilleSpClient as $sCleGrille => $sValeurGrille) {
            $sCleNouvelleDonnee = array_search($sValeurGrille['gts_refsoustarifs'], array_column($aNouvelleDonneeCsv, 'reference_si_sous_tarif'));
            if ($sCleNouvelleDonnee !== false) {
                // Calcul de pourcentage d'augmentation
                $fAugmente = (($aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'] - $sValeurGrille['gts_arhtlist'])/$sValeurGrille['gts_arhtlist']) * 100;
                $fAugmenteFormate = number_format($fAugmente, 2);

                // Calcul de pourcentage de baisse
                $fBaisse = (($sValeurGrille['gts_arhtlist'] - $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'] )/$sValeurGrille['gts_arhtlist']) * 100;
                $fBaisseFormate = number_format($fBaisse, 2);

                // L'élément a été trouvé dans les deux tableaux, comparez les valeurs
                if ($aGrilleSpClient[$sCleGrille] != $aNouvelleDonneeCsv[$sCleNouvelleDonnee]) {
                    if($sValeurGrille['gts_arhtlist'] === $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise']) {
                        $aAffichageChangement[] = "LIGNE IDENTIQUE : ".$sValeurGrille['gts_refsoustarifs'];
                        // Affichage dans le tableau kendo
                        // $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'] = $sValeurGrille['gts_arhtlist']." : identique ";
                        $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['etat'] = "Identique ";
                    } else if ($sValeurGrille['gts_arhtlist'] < $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise']) {
                        $aAffichageChangement[] = "TARIF AUGMENTE : ".$sValeurGrille['gts_refsoustarifs']." 
                        (".$sValeurGrille['gts_arhtlist']." -> ".$aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'].")";
                        // Affichage dans le tableau kendo
                        $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'] = $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'];
                        // $sValeurGrille['gts_arhtlist']." -> ".$aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'];
                        $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['etat'] = "Augmenté de ".$fAugmenteFormate." %";
                        $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['choix'] = true;
                    } else {
                        $aAffichageChangement[] = "TARIF BAISSE : ".$sValeurGrille['gts_refsoustarifs']." 
                        (".$sValeurGrille['gts_arhtlist']." -> ".$aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'].")";
                        // Affichage dans le tableau kendo
                        $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'] = $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'];
                        // $sValeurGrille['gts_arhtlist']." -> ".$aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'];
                        $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['etat'] = "Baissée de ".$fBaisseFormate." %";
                        $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['choix'] = true;
                    }
                    // $aAffichageChangement[] = "Ligne $sCleGrille a été modifiée.";
                }
            } else {
                // L'élément n'a pas été trouvé dans le tableau modifié, il a été supprimé
                $aAffichageChangement[] = "Ligne $sCleGrille en plus BDD vs CSV : " . $sValeurGrille['gts_refsoustarifs'];
                // L'élément en plus a été trouvé dans la base de données
                $aEnplusBase[] = $aGrilleSpClient[$sCleGrille];
            }
        }
        // Parcourir les éléments du tableau modifié pour détecter les nouvelles lignes
        foreach ($aNouvelleDonneeCsv as $sCleNouvelleDonnee => $itemNewData) {
            $sCleGrille = array_search($itemNewData['reference_si_sous_tarif'], array_column($aGrilleSpClient, 'gts_refsoustarifs'));
            
            if ($sCleGrille === false) {
                // L'élément a été trouvé uniquement dans le tableau modifié
                $aAffichageChangement[] = "Nouvelle ligne ajoutée dans le fichier CSV vs BDD : " . $itemNewData['reference_si_sous_tarif'];
                // Affichage dans le tableau kendo
                // $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise'] = $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['tarif_HT_remise']." : Ligne ajoutée";
                $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['etat'] = "Ligne ajoutée";
                $aNouvelleDonneeCsv[$sCleNouvelleDonnee]['choix'] = true;

            }
        }
        // Traitement de tableau BDD en plus vs CSV
        array_shift($aEntete);
        // Tableau contenant les nouvelles clés
        // Supprimer le premier élément : 'gts_aridlist'
        for($i = 0; $i < count($aEnplusBase); $i++) {
            array_shift($aEnplusBase[$i]);
        }
        array_shift($aChampSousTarif);
        // Tableau pour stocker le résultat pour remplacer les clés de $aEntete même taille
        $aColonneBasePlus = remplacerClees($aEntete, $aChampSousTarif);
        // Création des libellés de $aColonneBasePlus
        $aLibelleBasePlus = [];
        foreach($aColonneBasePlus as $sCleLibelle => $sValeurLibelle) {
            $aCreeColonne = creeColonne($sCleLibelle, $sValeurLibelle);
            array_push($aLibelleBasePlus, $aCreeColonne);
        }            
        // Rajouter une colonne dans $aLibelleBasePlus
        // $aPlus =['field' => 'choice', 'title' => 'Choice'];
        array_push($aLibelleBasePlus, creeColonne('choice', 'Supprimer'));

        // Détéction de doublon dans une colonne
        $colonneAAnalyser = 'gts_refsoustarifs';
        if (detecterDoublonDansColonne($aGrilleSpClient, $colonneAAnalyser)) {
            echo "Doublon détecté dans la colonne '".$colonneAAnalyser."'";
        } else {
            // echo "Aucun doublon détecté dans la colonne '".$colonneAAnalyser."'";
        }
        

    
        // Maintenant, $aNouvelleDonneeCsv contient les éléments du tableau de référence qui existent également dans le tableau à comparer
        // $action->lay->Set("TEST_HTML", $html);
        $action->lay->Set("NOUVELLE_DONNEE_CSV", json_encode($aNouvelleDonneeCsv));
        // $action->lay->Set("COLONNE_CSV", json_encode($aColonne));
        $action->lay->Set("EN_PLUS_BASE", json_encode($aEnplusBase));
        // $action->lay->Set("COLONNE_BASE", json_encode($aLibelleBasePlus));
    }

}

// Fonction permet de remplacer les clés d'un tableau associatif de taille différente
function remplacerCles($aEntree, $aNouvelleCle) {
    // $aResultat= [];
    foreach ($aEntree as $aSouTableau) {
        $aSouResultat = [];
        // Parcourer chaque élément du tableau associatif
        foreach ($aSouTableau as $sCle => $sValeur) {
            // Vérifier si la clé existe dans le tableau $aNouvelleCle
            if (isset($aNouvelleCle[$sCle])) {
                // Utiliser la nouvelle clé
                $sNouvelleCle = $aNouvelleCle[$sCle];
            } else {
                // Si la nouvelle clé n'existe pas, utilisez la clé d'origine
                $sNouvelleCle = $sCle;
            }
            // Ajouter la paire clé-valeur au sous-tableau du résultat
            $aSouResultat[$sNouvelleCle] = $sValeur;
        }
        // Ajouter le sous-tableau au tableau de résultat final
        $aResultat[] = $aSouResultat;
    }
    return $aResultat;
}      

// Fonction permet de remplacer les clés d'un tableau associatif de même taille 
function remplacerClees($aEntree, $aCle) {
    $aResultat = [];
    // les deux tableaux ont la même longueur
    if (count($aEntree) === count($aCle)) {
        $aValues = array_values($aEntree); // Récupérez les valeurs du tableau associatif
        for ($i = 0; $i < count($aCle); $i++) {
            $aResultat[$aCle[$i]] = $aValues[$i];
        }
    }
    return $aResultat;
}

// Fonction création de colonne
function creeColonne($name, $label){
    $resp = ["field" => $name, "title" => $label];
    return $resp;
}

// Detection de doublon
function detecterDoublonDansColonne($tableau, $colonne) {
    $valeurs = [];
    foreach ($tableau as $ligne) {
        // var_dump($ligne);
        // Assurez-vous que la colonne (clé) spécifiée existe dans la ligne
        if (array_key_exists($colonne, $ligne)) {
            $valeur = $ligne[$colonne];
            
            // Vérifiez si la valeur existe déjà dans le tableau $valeurs
            if (in_array($valeur, $valeurs)) {
                // Doublon détecté
                var_dump($valeur);

                return true;
            } else {
                // Ajoutez la valeur au tableau $valeurs
                $valeurs[] = $valeur;
            }
        }
    }
    // Aucun doublon détecté
    return false;
}
?>
