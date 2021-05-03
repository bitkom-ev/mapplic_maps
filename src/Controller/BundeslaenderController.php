<?php

/**
 * @file
 * Contains \Drupal\mapplic_maps\Controller\BundeslaenderController.
 */

namespace Drupal\mapplic_maps\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;

/**
 * Bundeslaender controller for the mapplic maps module.
 */
class BundeslaenderController extends ControllerBase {

  public function _mapplic_maps_bundeslaender_json() {
    $settings = [
      'mapwidth' => "600",
      'mapheight' => "800",
      'categories' => ['Bundeslaender'],
      'levels' => [],
    ];

    try {
      $config = \Drupal::config('mapplic_maps.settings');
      $settings['levels'][0]['id'] = 'mapplic-bundeslaender';
      $settings['levels'][0]['title'] = 'Deutsche Bundesländer';
      $settings['levels'][0]['map'] = '/modules/contrib/mapplic_maps/libraries/mapplic_maps/html/maps/bundeslaender.svg';
      $settings['levels'][0]['minimap'] = '/modules/contrib/mapplic_maps/libraries/mapplic_maps/html/maps/bundeslaender-mini.jpg';
    } catch (Exception $e) {
      \Drupal::logger('mapplic_maps')->error('entity_metadata_wrapper error in %error_loc', [
        '%error_loc' => __FUNCTION__ . ' @ ' . __FILE__ . ' : ' . __LINE__,
      ]);
      return new JsonResponse($settings);
    }

    $nodes = [];
    /**
     * taxonomy landmark anlegen: Bundeslaender / Deutschland / Europa / Welt
     */
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'mapplic_landmark')
      ->condition('status', 1)
      ->condition('field_mapplic_map_karte.entity:taxonomy_term.name', 'Bundeslaender', '=') // Bundeslaender / Deutschland / Europa / Welt
      ->sort('title', 'ASC');

    $result = $query->execute();

    if (isset($result) && !empty($result)) {
      $nodes = Node::loadMultiple($result);
    }
    if (empty($nodes)) {
      // Logs an error
      \Drupal::logger('mapplic_maps')
        ->error("Nodes mapplic_landmark and Taxonomy Länder Startups are still empty: " . $nodes);
      return new JsonResponse($settings);
    }

    foreach ($nodes as $node) {
      try {
        /**
         * */
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
          'pin' => $node->get('field_mapplic_pin')
            ->getValue()[0]['value'] ?: 'hidden',
          'fill' => $node->get('field_background_colour')
            ->getValue()[0]['value'] ?: '',
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
      } catch (Exception $e) {
        \Drupal::logger('mapplic_maps')->error('entity_metadata_wrapper error in %error_loc', [
          '%error_loc' => __FUNCTION__ . ' @ ' . __FILE__ . ' : ' . __LINE__,
        ]);
        return;
      }
    }

    rsort($settings['levels']);
    // Calling all modules implementing hook_mapplic_maps_settings_alter():
    \Drupal::moduleHandler()->alter('mapplic_maps_settings', $settings);

    return new JsonResponse($settings);
  }

}
