<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'local_social_wall', language 'en', branch 'dev_56_MM'
 * @package   local_social_wall
 * @author     Manisha M. 
 * @copyright  Paradiso Solutions LLC
*/

$string['pluginname'] = 'Social wall';
$string['descriptionname'] = 'Partage tes pensées';
$string['writecontent'] = 'Écrivez vos pensées ici ...'; 
$string['share'] = 'Publier';
$string['like'] = 'Comme';
$string['comment'] = 'Commentaire';
$string['posted'] = 'Publié il y a';
$string['required_field'] = 'Champ obligatoire manquant';
$string['delete_update'] = 'Êtes-vous sur de vouloir supprimer ce message?';
$string['delete_comment'] = 'Voulez-vous vraiment supprimer ce commentaire?';
$string['req_comment'] = 'Veuillez saisir un commentaire.';
$string['access_denied'] = "Accès refusé!! Vous n'avez pas accès pour effectuer cette action.";
$string['post_added'] = "Message créé avec succès ..";
$string['post_req'] = "Veuillez saisir du texte.";
$string['delete_post_success'] = "Message supprimé avec succès.";
$string['delete_cmnt_success'] = "Le commentaire a bien été supprimé.";
$string['comment_added'] = "Commentaire ajouté avec succès.";
$string['view_more'] = "Voir plus";
$string['view_less'] = "Voir moins";
$string['post_upd'] = "Publication mise à jour avec succès";
$string['invalidsesskey'] = "Sesskey non valide";
$string['expired'] = "Accès refusé!! Vous ne pouvez pas modifier après 30 minutes.";
$string['edit_expire_min'] = "Minutes d'expiration après modification";

// strings added for capability
$string['social_wall:view'] = "Voir le mur social";
$string['social_wall:addmessage'] = "Ajouter un nouveau message de mur social";
$string['local/social_wall:deletemessage'] = "Supprimer le message du mur social";

// strings added for timestamp
$string['sec_ago'] = '{$a} seconds';
$string['one_min'] = '1 minute ';
$string['min_ago'] = '{$a} minutes ';
$string['one_hour'] = '1 hour ';
$string['hour_ago'] = '{$a} hours ';
$string['one_day'] =  '1 jour ';  
$string['days_ago'] =  "{$a} jours";
$string['one_week'] = '1 semaine ';
$string['week_ago'] = '{$a} semaines ';
$string['one_month'] = '1 mois';
$string['month_ago'] = '{$a} mois ';
$string['one_year'] = '1 an ';
$string['year_ago'] = '{$a} ans ';
$string['writecomment'] = 'Ecrivez votre commentaire..';
$string['comment_added'] = "Commentaire posté avec succès.";

$string['social_wall_bg'] = 'Image d\'en-tête du mur social';
$string['social_wall_desc'] = 'Image d\'en-tête pour la page du mur socail <br> Formats de fichier image: JPG, GIF, PNG, taille recommandée 1347 x 612 px. Taille d\'image maximale: 5MB';
$string['page_grid'] = 'Grille de page';
$string['page_grid_desc'] = 'Grille de page de mur social';
$string['showall'] = "Afficher tout";

// Image
$string['uploadimage'] = 'Télécharger l\'image';
$string['extensionallowed'] = 'Désolé, seuls les fichiers jpg, jpeg et png sont autorisés à télécharger.';
$string['dirnotexist'] = 'Désolé, veuillez vous connecter avec l\'administrateur. Le répertoire de téléchargement n\'existe pas.';
$string['maxfilesizeallowed'] = 'La taille de fichier maximale autorisée est {$a} MB';
$string['probleminuploading'] = 'Désolé, il y a un problème lors du téléchargement de votre image. ';
$string['uploadsuccess'] = 'Fichier téléchargé avec succès!';
$string['movingissue'] = 'Désolé, quelque chose s\'est mal passé lors du déplacement de votre fichier!';
$string['noimage'] = 'Pas d\'image';
$string['uploadfaild'] = 'Désolé, votre fichier n\'a pas été téléchargé.';

// Video
$string['uploadvideo'] = 'Télécharger une video';
$string['videoextensionallowed'] = 'Désolé, seul le fichier mp4 est autorisé à télécharger.';
$string['filesallowed'] = '- Désolé, une seule vidéo peut être téléchargée';
$string['novideo'] = 'Pas de vidéo';
$string['probleminuploadingvideo'] = 'Désolé, il y a un problème lors du téléchargement de votre vidéo. ';

// files
$string['uploadfiles'] = 'Télécharger des fichiers';
$string['filesextensionallowed'] = 'Désolé, seuls les fichiers pdf, doc, docx, xls, xlsx, ppt et pptx sont autorisés à télécharger.';
$string['filesallowed'] = '- Désolé, 10 fichiers maximum peuvent être téléchargés';
$string['nofiles'] = 'Pas de fichier';
$string['probleminuploadingfiles'] = 'Désolé, il y a un problème lors du téléchargement de votre fichier. ';

// Timeline
$string['haventposted'] = 'Vous n\'avez pas encore posté';
$string['timeline'] = 'Mes publications';
$string['noofpost'] = 'Nombre de publications';
$string['backtosocialwall'] = 'Retour au fil d\'actualité';

// Notification
$string['commentedonpost'] = '{$a} a commenté votre message';
$string['likesapost'] = '{$a} aime votre message';
$string['commentedonpostemail'] = 'Bonjour {$a->currentusername}<br><br>{$a->postuser} a commenté votre message {$a->seepost}';
$string['likesapostemail'] = 'Bonjour {$a->currentusername}<br><br>{$a->postuser} aime votre message {$a->seepost}';
$string['notifications'] = 'Notifications';
$string['seemore'] = 'Voir plus';
$string['socialnotification'] = 'Notification de mur social';
$string['nonotification'] = 'Aucune notification';
$string['seepost'] = 'Voir l\'article';

// Posts Lazy Loading Setting
$string['default_post_load'] = 'Messages par défaut à afficher';
$string['default_post_load_desc'] = 'Afficher le nombre de publications par défaut lors du chargement de la page.';