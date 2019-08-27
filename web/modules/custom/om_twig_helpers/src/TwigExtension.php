<?php

namespace Drupal\om_twig_helpers;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\block\Entity\Block;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class ImageStyleUrlTwigExtension.
 *
 * @package Drupal\om_twig_helpers
 */
class TwigExtension extends Twig_Extension
{

  private $active_langs;

  /**
   * {@inheritdoc}
   */
  public function getName()
  {
    return 'om_twig_helpers';
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return array(
      new \Twig_SimpleFilter('path_to_class', [$this, 'getPathToClass']),
      new \Twig_SimpleFilter('no_last_word', [$this, 'getNoLastWord']),
      new \Twig_SimpleFilter('only_last_word', [$this, 'getOnlyLastWord']),
      new \Twig_SimpleFilter('style_color', [$this, 'getStyleColor']),
      new \Twig_SimpleFilter('style_background_color', [$this, 'getStyleBackgroundColor']),
      new \Twig_SimpleFilter('single_value_at', [$this, 'getSingleValueAt']),
      new \Twig_SimpleFilter('ged_formatter', [$this, 'getFormattedGeD']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new Twig_SimpleFunction('image_style_url', [$this, 'getImageStyleUrl']),
      new Twig_SimpleFunction('fallback_view_field', [$this, 'getFallbackViewField']),
      new Twig_SimpleFunction('print_block', [$this, 'printBlock'], ['is_safe' => ['html']]),
      new Twig_SimpleFunction('print_view', [$this, 'printView'], ['is_safe' => ['html']]),
      new Twig_SimpleFunction('print_entity', [$this, 'printEntity'], ['is_safe' => ['html']]),
      new Twig_SimpleFunction('theme_path', [$this, 'getThemePath']),
      new Twig_SimpleFunction('lang', [$this, 'getLang']),
      new Twig_SimpleFunction('simple_url', [$this, 'getSimpleUrl']),
      new Twig_SimpleFunction('link_active', [$this, 'getLinkActive']),
      new Twig_SimpleFunction('term_slug', [$this, 'getTermSlug']),
      new Twig_SimpleFunction('tt', [$this, 'tt'], ['is_safe' => ['html']]),
      new Twig_SimpleFunction('svg_icon', [$this, 'svgIcon'], ['is_safe' => ['html']]),
      new Twig_SimpleFunction('svg_file_icon', [$this, 'getSvgFileIcon'], ['is_safe' => ['html']]),
      new Twig_SimpleFunction('make_attribute', [$this, 'makeAttribute']),
      new Twig_SimpleFunction('field_empty', [$this, 'isFieldEmpty']),
      new Twig_SimpleFunction('field_bool', [$this, 'getFieldBool']),
      new Twig_SimpleFunction('get_language_links', [$this, 'getLanguageLinks']),

      new Twig_SimpleFunction('user_picture', [$this, 'getUserPicture']),

      // GDI specific
      new Twig_SimpleFunction('get_user_role', 'get_user_role'),
    ];
  }


  /**
   * Get renderable array of node field. Returns field value from available alternative
   * language if current value is empty.
   *
   * @param Node $node
   *  Node object
   * @param string $field
   *  Name of the field to get
   * @param string $view_mode
   *  View mode to use when returning field
   *
   * @return array
   *  Renderable array
   */

  public function getFallbackViewField($node, $field, $view_mode = 'default') {

    if($node->get($field)->isEmpty()) {
      if($node) {
        $alt_lang = $node->language()->getId() == 'ka' ? 'en' : 'ka';
        if($node->hasTranslation($alt_lang)) {
          $node_trans = $node->getTranslation($alt_lang);
          if(!$node_trans->get($field)->isEmpty()) {
            return $node_trans->get($field)->view($view_mode);
          }
        }
      }
    }

    // give up if nothing worked
    return $node->get($field)->view($view_mode);
  }


  /**
   * Returns "active" class or empty string if passed URL is current page
   * @param string $url
   * @return string
   *
   * {{ link_active('/page') }} -> true
   * {{ link_active('page') }}  -> true
   * {{ link_active('<front>') }} -> false
   */

  public function getLinkActive($url) {
    $url = ltrim($url, '/');
    $url = '/'.$url;

    $current_path = \Drupal::service('path.current')->getPath();
    $current_alias = Unicode::strtolower(\Drupal::service('path.alias_manager')->getAliasByPath($current_path));
    $page_match = \Drupal::service('path.matcher')->matchPath($current_alias, $url) || (($current_path != $current_alias) && \Drupal::service('path.matcher')->matchPath($current_path, $url));
    return $page_match ? 'active' : '';
  }


  /*
   * Gets full URL for styled image by URI and Style Name.
   *
   * Example: image_style_url('public://image.png', 'thumbnail')
   */
  public function getImageStyleUrl($uri, $style_name) {
    return ImageStyle::load($style_name)->buildUrl($uri);
  }


  /*
   * Converts /path/ to class name. Usefull in menus. Removes language prefix.
   *
   * Example: {{ '/ge/about/contact' | path_to_class }} -> about-contact
   */
  public function getPathToClass($path) {

    $path = array_filter(explode('/', $path));
    $path = array_values($path);

    $langs = $this->getActiveLangs();

    if(isset($path[0]) && !empty($langs) && strlen($path[0]) == 2){
      unset($path[0]);
    }

    $path = implode('-', $path);

    return Html::getClass($path);

  }

  /*
   * Those two functions are useful for wrapping last word + custom element
   * in <nobr> or css-alternative tags.
   *
   * For example, when you have to display {title}{save_icon} and want to wrap
   * last word and save icon in a tag.
   */
  public function getNoLastWord($input){
    if(!empty($input[0]['#context']['value'])) {
      $input = $input[0]['#context']['value'];
    } elseif(!empty($input['value'])) {
      $input = $input['value'];
    } elseif(is_array($input)) {
      return $input;
    }

    $last_space = mb_strrpos($input, ' ');
    return Unicode::substr($input, 0, $last_space);
  }
  public function getOnlyLastWord($input){
    if(!empty($input[0]['#context']['value'])) {
      $input = $input[0]['#context']['value'];
    } elseif(!empty($input['value'])) {
      $input = $input['value'];
    } elseif(is_array($input)) {
      return $input;
    }

    $last_space = mb_strrpos($input, ' ');
    return Unicode::substr($input, $last_space);
  }


  /*
   * Gets color value (#ff0000) and returns style attribute with it. Or returns nothing.
   *
   * Use with field_raw - {{ field_color | field_raw | style_color }}
   */

  public function getStyleColor($input){
    if(!empty($input)) {
      if(is_array($input) && isset($input['color'])) {
        $input = $input['color'];
      } else {
        $input = (string) $input;
      }
      return ['#markup'=>' style="color:'.$input.'" '];
    }
    return '';
  }
  public function getStyleBackgroundColor($input){
    if(!empty($input)) {
      if(is_array($input) && isset($input['color'])) {
        $input = $input['color'];
      } else {
        $input = (string) $input;
      }
      return ['#markup'=>' style="background-color:'.$input.'" '];
    }
    return '';
  }


  /*
   * Returns single value of multi-value field for printing.
   *
   * This will print first value:
   *    {{ content.field_topics | single_value_at }}
   * As well as this:
   *    {{ content.field_topics | single_value_at(0) }}
   * As well as this:
   *    {{ content.field_topics | single_value_at('first') }}
   *
   * Pass any index to print corresponding value (e.g. use 1 to print value number 2).
   *
   * Pass 'first' or 'last' to print first or last items.
   *
   * If no item at index, all items will be printed (e.g. single_value_at(99999) will print all values).
   */

  public function getSingleValueAt($input, $index) {
    if(is_array($input)) {
      $children = Element::children($input);
      if($children) {
        if($index === 'first') {
          $index = 0;
        } elseif($index === 'last') {
          $index = count($children) - 1;
        } else {
          $index = (int)$index;
        }

        if(isset($children[$index])){
          $child_to_keep = $children[$index];
          foreach($children as $child) {
            if($child === $child_to_keep){
              $child_el_to_keep = $input[$child];
            }
            unset($input[$child]);
          }
          $input[0] = $child_el_to_keep;
        }
      }
    }
    return $input;
  }

  /*
   * Returns formatted GeD
   *
   */
  public function getFormattedGeD($input){
    $GEDHelper = \Drupal::service('girchi_users.ged_helper');
    return $GEDHelper::getFormattedGED($input);
  }


  /*
   * Prints block HTML by block ID.
   *
   * Example: print_block('sacdelibloki')
   */
  public function printBlock($block_id) {
    $block = Block::load($block_id);
    if($block) {
      return \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);
    } else {
      return '';
    }
  }


  /*
   * Returns value of field_slug for given term ID.
   * Used in CSV output view for Facebook Product Feed.
   *
   * Example: term_slug(457)
   */
  public function getTermSlug($tid) {
    $tid = "".$tid; // if passed as Markup
    $term = Term::load($tid);
    if($term) {
      if(!$term->get('field_slug')->isEmpty()){
        return $term->get('field_slug')->value;
      }
    }
    return '';
  }

  /*
   * Gets true or false by passing boolean field.
   * Only first value is evaluated if boolean field is multivalue.
   *
   * Example: {% if field_bool(content.field_is_featured) %}
   *
   * Returns null if field is empty.
   */
  public function getFieldBool($build) {
    if (isset($build['#items']) && $build['#items'] instanceof \Drupal\Core\TypedData\TypedDataInterface) {
      $values = $build['#items']->getValue();

      if(!empty($values)) {
        return ($values[0]['value'] == 1);
      } else {
        return null;
      }
    }
  }

  /*
   * Prints view HTML by view name and display name.
   * Optionally, pass extra arguments for view.
   *
   * Example: print_view('view_name', 'block_1', 'view_arg')
   */
  public function printView() {
    $args = func_get_args();
    $view = call_user_func_array('views_embed_view', $args);
    return $view;
  }

  /**
   * Returns the render array for an entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param mixed $id
   *   The ID of the entity to render.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return null|array
   *   A render array for the entity or NULL if the entity does not exist.
   */
  public function printEntity($entity_type, $id = NULL, $view_mode = NULL, $langcode = NULL) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity = $id
      ? $entity_type_manager->getStorage($entity_type)->load($id)
      : \Drupal::routeMatch()->getParameter($entity_type);
    if ($entity) {
      $render_controller = $entity_type_manager->getViewBuilder($entity_type);
      return $render_controller->view($entity, $view_mode, $langcode);
    }
  }


  /*
   * Gets full path to theme folder (including base path) without trailing slash.
   *
   * If file path is passed, concatenated full URL will be returned, with correct handling of trailing slash.
   *
   * Example: theme_path()                   -> /themes/custom/omedia
   *          theme_path('images/logo.png')  -> /themes/custom/omedia/images/logo.png
   *          theme_path('/images/logo.png') -> /themes/custom/omedia/images/logo.png
   */
  public function getThemePath($file_path = false, $absolute = false) {
    $theme_path = base_path() . \Drupal::theme()->getActiveTheme()->getPath();

    if($file_path) {
      $file_path = ltrim($file_path, '/');
      $theme_path = $theme_path .'/'. $file_path;
    }

    if($absolute) {
      return \Drupal::request()->getSchemeAndHttpHost() . $theme_path;
    } else {
      return $theme_path;
    }
  }

  /*
   * Gets dummy string (presumably alias) and prepends base path and language prefix.
   *
   * Example: simple_url('about')            -> /ge/about
   *          simple_url('/about')           -> /ge/about
   *          simple_url('/about', true)     -> http://domain.com/ge/about
   *          simple_url('/about')           -> /site/ge/about (if Drupal core is in domain.com/site)
   */
  public function getSimpleUrl($url, $absolute = false, $query_array = []) {
    $base = Url::fromUri('internal:/', ['absolute' => $absolute]);
    $url = ltrim($url, '/');

    $query_str = '';
    if(!empty($query_array)){
      $query_str = '?'.UrlHelper::buildQuery($query_array);
    }

    return $base->toString().'/'.$url.$query_str;
  }

  /*
   * Checks if content.field is empty
   *
   * Example: field_empty(content.field_name)   -> true
   */
  public function isFieldEmpty($build) {
    if (!$this->isFieldRenderArray($build)) {
      return true;
    }

    if (isset($build['#items']) && !empty($build['#items'])) {
      return false;
    }

    return true;
  }

  /*
   * Returns current language code
   *
   * Example: lang()   -> ka
   */
  public function getLang($return_object = false) {
    $current_lang = \Drupal::languageManager()->getCurrentLanguage();
    if($return_object) {
      return $current_lang;
    } else {
      return $current_lang->getId();
    }
  }

  private function getActiveLangs(){
    if(empty($this->active_langs)) {
      $this->active_langs = \Drupal::languageManager()->getLanguages();
      uasort($this->active_langs, function ($a, $b) {
        return $a->getWeight() - $b->getWeight();
      });
    }
    return $this->active_langs;
  }

  /*
   * Fake translation function, returns one of passed arguments in current language.
   *
   * Example: tt('string in georgian', 'string in english')
   *      or: tt(['string in georgian', 'string in english'])
   *
   * Argument order is based on Drupal active languages order.
   *
   * Pass language object as a last argument to force that language.
   */
  public function tt() {

    $args_num = func_num_args();
    if($args_num <= 0){
      return '';
    }

    $args = func_get_args();

    if(is_array($args[0])){
      $args = $args[0];
    }

    // forced language
    end($args);
    $last_key = key($args);
    $last_arg = $args[$last_key];
    reset($args);

    if($last_arg instanceof Language) {
      $current_lang_id = $last_arg->getId();
      unset($args[$last_key]);
    } else {
      $current_lang_id = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }

    $active_langs = $this->getActiveLangs();

    // Find place number of current language in Languages list
    $current_lang_place = array_search($current_lang_id, array_keys($active_langs));

    // If we don't have argument on that place (for example, 3 languages and 2 args)
    if(!isset($args[$current_lang_place])) {
      return $args[0];
    }

    return $args[$current_lang_place];

  }

  /*
   * Prints SVG icon.
   */
  public function svgIcon($icon_name, $extra_classes = ''){

    if(empty($icon_name)){
      return '';
    }

    $icon_sprite = theme_get_setting('omedia_svg_icon_file') ?: '';

    $query_string = \Drupal::state()->get('system.css_js_query_string') ?: '0';
    $file = $this->getThemePath($icon_sprite);
    $query_string_separator = (strpos($file, '?') !== false) ? '&' : '?';
    $file = $file . $query_string_separator . $query_string;

    $icon_html = '<svg role="image" class="icon-'.$icon_name.' '.$extra_classes.'"><use xlink:href="'.$file.'#icon-'.$icon_name.'"></use></svg>';

    return $icon_html;

  }


  /**
   * Returns SVG file url
   *
   * @param string $file_name
   *  Relative to theme folder
   *
   * @return string
   */
  public function getSvgFileIcon($file_name) {
    $file_uri = DRUPAL_ROOT . $this->getThemePath($file_name);

    $svg = file_get_contents($file_uri);
    $svg = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $svg);

    return $svg;

  }


  /**
   * Creates an Attribute object.
   *
   * @param array $attributes
   *   (optional) An associative array of key-value pairs to be converted to
   *   HTML attributes.
   *
   * @return \Drupal\Core\Template\Attribute
   *   An attributes object that has the given attributes.
   *
   * @todo This should be removed with Drupal core 8.3.x as it provides create_attribute() twig function out of the box.
   */
  public function makeAttribute(array $attributes = []) {
    return new Attribute($attributes);
  }


  public function getLanguageLinks($active = 'include') {
    $language_manager = \Drupal::languageManager();
    $current_language = $language_manager->getCurrentLanguage()->getId();
    $languages = $language_manager->getLanguages();
    $links = [];
    $fromRoute = '<current>';
    $status = \Drupal::requestStack()->getCurrentRequest()->attributes->get('exception');
    if ($status && $status->getStatusCode() == 404){
      $fromRoute = '<front>';
    }
    if (count($languages) > 1) {
      foreach ($languages as $lid => $item) {
        $url = Url::fromRoute($fromRoute, [], ['language' => $item]);
        if(strpos($url->toString(), '/node/') !== false || strpos($url->toString(), '/term/') !== false) {
          $url = Url::fromRoute('<front>');
        }
        $links[$lid] = [
          'title' => $item->getName(),
          'url' => $url,
        ];
      }
      if($active === false) {
        // don't include active language
        unset($links[$current_language]);
      } elseif($active == 'first') {
        // Place active language ontop of list so it can be easily hidden with css :first
        $active = $links[$current_language];
        $links = [$current_language => $active] + $links;
      }
    }
    return $links;
  }


  /**
   * Returns user picture or name-letter circle
   *
   * @param User $user
   *  User object
   * @param $picture_render_array
   *  Render array of current picture if available
   * @param $view_mode
   *  View mode to render picture in
   * @param $fake_avatar_link
   *  Set to empty to link it to profile, set to false to not link at all.
   * @return mixed
   *
   * Usage:
   * {{ user_picture(user, content.user_picture, 'account_info_block') }}
   */

  public function getUserPicture(User $user, $picture_render_array, $view_mode, $fake_avatar_link = '') {
    $build = [];

    if($user->get('user_picture')->isEmpty()) {
      $name = $user->getDisplayName();
      $length = Unicode::strlen($name);
      $letter = null;
      for($i = 0; $i < $length; $i++) {
        $substr = Unicode::substr($name, $i, 1);
        if(preg_match("/[a-zა-ჰа-я]/i", $substr)){
          $letter = Unicode::strtoupper($substr);
          break;
        }
      }
      if(!$letter){
        $letter = 'X';
      }
      $markup = '<span class="user-avatar-fake">'.$letter.'</span>';
      if($fake_avatar_link !== false) {
        if($fake_avatar_link == '') {
          $fake_avatar_link = $user->url();
        }
        $markup = '<a href="'.$fake_avatar_link.'">'.$markup.'</a>';
      }
      $build = [
        '#markup' => $markup,
      ];
    } else {
      if(empty($picture_render_array)) {
        $vb = \Drupal::entityTypeManager()->getViewBuilder('user');
        $user_view = $vb->view($user, $view_mode);
        $build = $user_view['user_picture'];
      } else {
        $build = $picture_render_array;
      }
    }

    if(!$user->get('field_user_type')->isEmpty()) {
      $user_type = $user->get('field_user_type')->value;
    } else {
      $user_type = 'default';
    }

    $build['#prefix'] = '<div class="user-avatar user-avatar-'.$view_mode.' user-type-'.$user_type.'">';
    $build['#prefix'] .= '<div class="user-avatar-inner">';
    $build['#suffix'] = '</div></div>';

    return $build;

  }


  /**
   * Checks whether the render array is a field's render array.
   *
   * @param $build
   *   The renderable array.
   *
   * @return bool
   *   True if $build is a field render array.
   */
  protected function isFieldRenderArray($build) {

    return isset($build['#theme']) && $build['#theme'] == 'field';
  }
}
