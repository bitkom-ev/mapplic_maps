<?php

/**
 * @file
 * Contains \Drupal\mapplic_maps\Controller\GermanyController.
 */

namespace Drupal\mapplic_maps\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Germany controller for the mapplic maps module.
 */
class GermanyController extends ControllerBase
{

  public function _mapplic_maps_germany_json()
  {
    $settings = [
      'mapwidth' => "600",
      'mapheight' => "800",
      'categories' => ['Germany'],
      'levels' => [],
    ];
    //dump($settings);
    //\Drupal::logger('mapplic_maps')->error("mapplic_maps Germany settings: " . print_r($settings, true));

    try {
      $config = \Drupal::config('mapplic_maps.settings');
      $settings['levels'][0]['id'] = 'mapplic-germany';
      $settings['levels'][0]['title'] = 'Germany';
      $settings['levels'][0]['map'] = '/modules/contrib/mapplic_maps/libraries/mapplic_maps/html/maps/germany.svg';
      $settings['levels'][0]['minimap'] = '/modules/contrib/mapplic_maps/libraries/mapplic_maps/html/maps/germany-mini.jpg';
    } catch (Exception $e) {
      \Drupal::logger('mapplic_maps')->error('mapplic_maps error in %error_loc', ['%error_loc' => __FUNCTION__ . ' @ ' . __FILE__ . ' : ' . __LINE__, ]);
      return;
    }

    $nodes = [];
    /**
     * SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS  WHERE TABLE_SCHEMA = 'prod_smartschool' AND TABLE_NAME = 'taxonomy_term_field_data';
     * tid     * vid     * name
     * taxonomy landmark anlegen: Deutschland / Europa / Welt
     * 557     557     landmark        de      Deutschland
     * 558     558     landmark        de      Europa  NULL
     * 559     559     landmark        de      Welt    NULL
     *  drush -l smartschool sql-query "SELECT * FROM prod_smartschool.taxonomy_term_field_data WHERE vid = 'landmark';"
     */
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'mapplic_landmark')->condition('status', 1)->condition('field_mapplic_map_karte', 557) ->sort('title', 'ASC');

    $result = $query->execute();
    if (isset($result) && !empty($result)) {
      $nodes = Node::loadMultiple($result);
    }

    if (empty($nodes)) {
      \Drupal::logger('mapplic_maps')->error("mapplic_maps error: Nodes mapplic_landmark and Taxonomy landmark with: Deutschland are still empty: " . print_r($nodes));
      return;
    }

    foreach ($nodes as $node) {
      try {
        $thumb = NULL;
        $uri = NULL;
        if (isset($node->get('field_thumb_image')
            ->getValue()[0]['target_id'])) {
          $thumb = $node->get('field_thumb_image')->getValue()[0]['target_id'];
        }
        if ($thumb != NULL) {
          $file = File::load($thumb);
          $uri = $file->getFileUri();
        }
        $thumb_url = NULL;
        if ($uri != NULL) {
          $thumb_url = ImageStyle::load('mapplic_thumb')
            ->buildUrl($uri); //image_style_url("mapplic_thumb", $thumb['uri']);
        }

        $description = NULL;
        $about = NULL;
        if ($node->__isSet('body')) {
          $description = $node->get('body')->getValue();
          if ($description != NULL) {
            $about = strip_tags($description[0]['summary']);
            $description = strip_tags($description[0]['value'], '<ul><ol><li><a><b><p><br><div><img>');
          }
        }
        /**
         * optional fields check if:
         */
        $id = "";
        if (isset($node->get('field_mapplic_map_id')->getValue()[0]['value'])) {
          $id = $node->get('field_mapplic_map_id')->getValue()[0]['value'];
        }
        $link = NULL;
        if (isset($node->get('field_link')->getValue()[0]['uri'])) {
          $link = $node->get('field_link')->getValue()[0]['uri'];
        }

        $settings['levels'][0]['locations'][] = [
          'id' => $id,
          'title' => $node->getTitle(),
          'description' => $description,
          'label' => $about,
          'pin' => $node->get('field_mapplic_pin')->getValue()[0]['value'] ?: 'hidden',
          'fill' => $node->get('field_background_colour')->getValue()[0]['value'] ?: '',
          'thumbnail' => $thumb_url,
          'link' => $link,
          // $node->get('field_link')->getValue()[0]['uri'],
          'zoom' => $node->get('field_zoom')->getValue()[0]['value'],
          //'pin' => "hidden",
          'x' => $node->get('field_mapplic_pos_x')->getValue()[0]['value'],
          //$wrapper->mapplic_pos_x->value(),
          'y' => $node->get('field_mapplic_pos_y')->getValue()[0]['value'],
          //$wrapper->mapplic_pos_y->value(),
        ];
        //dump($settings);

      } catch (Exception $e) {
        \Drupal::logger('mapplic_maps')->error('mapplic_maps error in %error_loc', ['%error_loc' => __FUNCTION__ . ' @ ' . __FILE__ . ' : ' . __LINE__,]);
        return;
      }
    }

    rsort($settings['levels']);
    // Calling all modules implementing hook_mapplic_maps_settings_alter():
    \Drupal::moduleHandler()->alter('mapplic_maps_settings', $settings);

    return new JsonResponse($settings);
  }

}
