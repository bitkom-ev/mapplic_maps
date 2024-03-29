<?php

/**
 * @file
 * Contains \Drupal\mapplic_maps\Plugin\Block\MapplicMapsEurope.
 */

namespace Drupal\mapplic_maps\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'mapplic_maps' Europe block.
 *
 * @Block(
 *   id = "mapplic_maps_europe_block",
 *   admin_label = @Translation("Mapplic Maps Europe block"),
 *   category = @Translation("Mapplic Maps Europe block")
 * )
 */
class MapplicMapsEurope extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    global $base_url;
    $config = \Drupal::config('mapplic_maps.settings');

    //to generate: maps/data/europe.json
    $source = Url::fromRoute("maps_europe_json.json", [], ['absolute' => TRUE]);
    //kint($base_url);

    $mapplicMapsSettings = [
      'mapplic_maps' => [
        'action' => 'tooltip',
        'source' => $base_url . "/maps/data/europe.json",
        'animate' => $config->get('mapplic_animate'),
        'alphabetic' => false,
        'clearbutton' => true,
        'developer' => $config->get('mapplic_developer_mode'),
        'deeplinking' => true,
        'height' => '1200', // $config->get('mapplic_map_height'),
        'width' => '1200', // $config->get('mapplic_map_width'),
        'hovertip' => true,
        'fillcolor' => '#ff0000',
        'fullscreen' => $config->get('mapplic_fullscreen'),
        'landmark' => true,
        'lightbox' => false,
        'locations' => $config->get('mapplic_locations'),
        'mapfill' => $config->get('mapplic_mapfill'),
        'mousewheel' => true,
        'maxscale' => $config->get('mapplic_max_scale'),
        'minimap' => true,
        'markers' => true,
        'selector' => '[id^=landmark] > *, svg > #items > *',
        'search' => $config->get('mapplic_search'),
        'sidebar' => $config->get('mapplic_sidebar'),
        'smartip' => true,
        #'thumbholder' => true,
        'zoombuttons' => $config->get('mapplic_zoombuttons'),
        'zoomoutclose' => true,
        'zoom' => $config->get('mapplic_zoom'),
      ],
    ];
    //kint($mapplicMapsSettings);

    return [
      '#theme' => '',
      '#type' => 'markup',
      '#markup' => '<div id="mapplic_maps"></div>',
      '#attached' => [
        'library' => [
          # internal library in module: 'your_module/library_name'
          'mapplic_maps/mapplic_maps'
        ],
        'drupalSettings' => $mapplicMapsSettings,
      ],
    ];
  }

}
