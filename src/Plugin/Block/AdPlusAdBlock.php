<?php
/**
* @file
* Contains \Drupal\adplus\Plugin\Block\AdPlusAdBlock.
*/

namespace Drupal\adplus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;

/**
* Provides AdPlus: Ad Block.
*
* @Block(
* id = "adplus_ad_block",
* admin_label = @Translation("AdPlus: Ad Block"),
* category = @Translation("Blocks")
* )
*/
class AdPlusAdBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'adplus_ad_location' => 0,
      'adplus_ad_type' => 'short',
    );
  }


  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', "adplus_ad_location");
    $tids = $query->execute();
    $options = array();
    if(count($tids)>0) {
      foreach($tids as $tid) {
        $term = \Drupal\taxonomy\Entity\Term::load($tid);
        $options[$term->tid->value] = $term->name->value;
      }
    }

    $form['adplus_ad_location'] = array(
      '#type' => 'select',
      '#title' => $this->t('Ad Location'),
      '#description' => $this->t('Please select an ad location'),
      '#options' => $options,
      '#default_value' => isset($config['adplus_ad_location']) ? $config['adplus_ad_location'] : '',
    );
    $form['adplus_ad_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Ad Type'),
      '#description' => $this->t('Please select an ad type'),
      '#options' => array(
        'long' => $this->t('Long'),
        'medium' => $this->t('Medium'),
        'short' => $this->t('Short'),
      ),
      '#default_value' => isset($config['adplus_ad_type']) ? $config['adplus_ad_type'] : 'short',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['adplus_ad_location'] = $form_state->getValue('adplus_ad_location');
    $this->configuration['adplus_ad_type'] = $form_state->getValue('adplus_ad_type');

	$message = t('Ad block configuration has been saved successfully.') ;
	drupal_set_message($message);
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = false) {
    //return $account->hasPermission('access content');
	if($account->hasPermission('access content')) {
	  return AccessResult::allowed();
	}
	return AccessResult::forbidden();
  }


  /**
  * {@inheritdoc}
  */
  public function build() {
    global $base_url;

  	$config = $this->getConfiguration();
    $ad_location = isset($config['adplus_ad_location']) ? $config['adplus_ad_location'] : '';
    $ad_type = isset($config['adplus_ad_type']) ? $config['adplus_ad_type'] : '';

	$build = array();

	$query = \Drupal::entityQuery('node')
		->condition('status', 1)
		->condition('field_ad_location.target_id', $ad_location, '=')
		->condition('field_ad_type.value', $ad_type, '=')
		->condition('field_start_date.value', date('Y-m-d'), '<=')
		->condition('field_end_date.value', date('Y-m-d'), '>=')
		->condition('field_active.value', 1);

	$group = $query->orConditionGroup()
		->condition('type', array('adplus_imagead', 'adplus_textad'), 'IN');
	$query->condition($group);

	//dsm($query);
	$nids = $query->execute();

	if(count($nids)==0) {
	  return $build;
	}

	$ad_nid = 0;
	if(count($nids) >= 2) {
	  shuffle($nids);
	  $rand_keys = array_rand($nids, 2);

	  $ad_nid = $nids[$rand_keys[0]];
	}
	else {
	  $arr_nids = array_values($nids);
	  $ad_nid = $arr_nids[0];
	}

	$node = Node::load($ad_nid);
	$title = $node->get('title')->value;
	$type = $node->get('type')->target_id;
	$field_ad_link = $node->get('field_ad_link')->value;
    $ad_type = $node->get('field_ad_type')->value;

	if(isset($type) && $type == 'adplus_imagead') {
	  $field_ad_image = $node->get('field_ad_image')->getValue();
	}

	if(isset($type) && $type == 'adplus_textad') {
	  $body = $node->get('body')->value;
	}

	$link = (isset($field_ad_link) ? $field_ad_link : '');
	$link_arr = parse_url($link);
	if(isset($link_arr['scheme']) && ($link_arr['scheme'] == 'http' || $link_arr['scheme'] == 'https')) {
	  //do nothing
	}
	else {
	  $link = $base_url.$link;
	}
	if($link == '') $link = $base_url;

	//prepare the ad link
	$ad_link = $base_url.'/adplus/redirect?ad_id='.$ad_nid.'&url='.$link;

	//save the impression to node
	$node->set("field_ad_impression", $node->get('field_ad_impression')->value + 1);
	$node->save();

	$output = '';
	if(isset($type) && $type == 'adplus_imagead') {
	  $output = '<div class="cycle-slideshow"
					data-cycle-fx="fade"
					data-cycle-timeout="2000"
					data-cycle-slides="> a"
				>';
	  if(isset($field_ad_image) && count($field_ad_image)>0) {
		foreach($node->field_ad_image as $image) {
		  $path = $image->entity->getFileUri();
		  if($ad_type == 'long') {
		    $url = ImageStyle::load('adplus_large')->buildUrl($path);
		  }
		  else if($ad_type == 'medium') {
		    $url = ImageStyle::load('adplus_medium')->buildUrl($path);
		  }
		  else if($ad_type == 'short') {
		    $url = ImageStyle::load('adplus_short')->buildUrl($path);
		  }

		  $output .= '<a target="_blank" href="'.$ad_link.'">';
		  $output .= '	<img src="'.$url.'" alt="'.$title.'" >';
		  $output .= '</a>';
		}
	  }
	  $output .= '</div>';
 	}
 	else if(isset($type) && $type=='adplus_textad') {
	  $output = '<div class="cycle-slideshow"
					data-cycle-fx="fade"
					data-cycle-timeout="2000"
					data-cycle-slides="> div"
				>';

	  $output .= '	<div>';
	  $output .= '	  <a target="_blank" href="'.$ad_link.'">';
	  $output .= 	    $body;
	  $output .= '	  </a>';
	  $output .= '	</div>';
	  $output .= '</div>';
	}

    $build = [
      '#markup' => \Drupal\Core\Render\Markup::create($output),
      '#prefix' => '<div class="adplus-display">',
      '#suffix' => '</div>',
	  '#cache' => [
		'contexts' => ['languages'],
		'tags' => ['node:' . $ad_nid],
    	'max_age'=> 0,
  	  ],
    ];

	return $build;
  }

}
