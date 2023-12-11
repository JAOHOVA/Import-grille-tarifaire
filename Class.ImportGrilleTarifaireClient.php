<?php

namespace Nevea\GrilleTarifaireClient;


use Dcp\HttpApi\V1\Crud\Crud;


class ImportGrilleTarifaireClient extends Crud
{
    public function create() {
        //throw new \Exception('Méthode non autorisée');
        // echo "test POST";
        /*$aVariable = $this->contentParameters;
        echo print_r($aVariable);
        echo $aVariable[aModifier];*/

        
    }

    /**
     * Read a ressource
     * @param string|int $resourceId Resource identifier
     * @return mixed
     * @throws Exception
     */
    public function read($resourceId) {
        // Récupérer les données envoyer
        $aVariable = $this->contentParameters;
        $aModif = $aVariable["aModifier"];
        $aSuppM = $aVariable["aSupprimer"];
        // Récupérer les valeurs de tableau 1 envoyé
        foreach($aModif  as $sValeurModif)
        {
            $aMod[] = explode('@', $sValeurModif);
        }
        // Changer la clé de $aMod
        // Création d'un nouveau tableau associatif à partir de $aMod
        // Tableau contenant les nouvelles clés
        $aNouvelleCle = [
            'tarif_HT_remise',
            'reference_si_sous_tarif'
        ];

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
        $aModifier = remplacerCles($aMod, $aNouvelleCle);
        // Récupérer le paramètre de l'url pour avoir l'id du document
        $aVariable = $this->urlParameters;
        $oGrilleSpecifiqueClient = new_Doc("", $aVariable["idGrilleTarifaireClient"]);
        // Récupérer data ORM
        $aGrilleSpClientORM = $oGrilleSpecifiqueClient->getAttributeValue("gts_t_containt");

        // Tableau d'origine $aGrilleSpClientORM
        // Tableau modifié $aModifier
        // Comparaison des valeurs de tableau et mettre à jour le champ gts_arhtlist
        foreach ($aGrilleSpClientORM as &$sValeurGrille) {
            $gts_refsoustarifs = $sValeurGrille['gts_refsoustarifs'];
            foreach ($aModifier as $sValeurMod) {
                $reference_si_sous_tarif = $sValeurMod['reference_si_sous_tarif'];
                if($gts_refsoustarifs === $reference_si_sous_tarif) {
                    $sValeurGrille['gts_arhtlist'] = $sValeurMod['tarif_HT_remise'];
                }
            }
            unset($sValeurGrille); // Détruire la référence de la dernière boucle foreach pour éviter des problèmes
        }
        $oGrilleSpecifiqueClient->setAttributeValue('gts_t_containt',$aGrilleSpClientORM);
        $oGrilleSpecifiqueClient->store();
            
        // Traitement pour la suppression
        // Récupérer les valeurs de aSuppM envoyé
        foreach($aSuppM as $sValeurSupp)
        {
            $aSupp[] = explode('@', $sValeurSupp);
        }
        // Remplacer les clés de $aSupp
        $aSupprimer = remplacerCles($aSupp, $aNouvelleCle);
        // Comparaison des valeurs de tableau et supprimer les lignes selectionner
        foreach ($aSupprimer as $sValeurASupp) {
            $reference_si_sous_tarif = $sValeurASupp['reference_si_sous_tarif'];
            foreach ($aGrilleSpClientORM as $index => $itemGrilleORM) {
                $gts_refsoustarifs = $itemGrilleORM['gts_refsoustarifs'];
                if($gts_refsoustarifs === $reference_si_sous_tarif) {
                    unset($aGrilleSpClientORM[$index]);
                    break; // Sortir de la boucle dès qu'une correspondance est trouvée
                }
            }
        }

        $oGrilleSpecifiqueClient->setAttributeValue('gts_t_containt',$aGrilleSpClientORM);
        $oGrilleSpecifiqueClient->store();
        
    }

    
    /**
     * Update the ressource
     * @param string|int $resourceId Resource identifier
     * @return mixed
     * @throws Exception
     */
    public function update($resourceId) {
        throw new \Exception('Méthode non autorisée');
        
    }

    /**
     * Delete ressource
     * @param string|int $resourceId Resource identifier
     * @return mixed
     * @throws Exception
     */
    public function delete($resourceId) {
        throw new \Exception('Méthode non autorisée');
    }
}