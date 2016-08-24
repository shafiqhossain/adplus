<?php
/**
* @file
* Contains \Drupal\adplus\Form\AdPlusResetForm.
*/

namespace Drupal\adplus\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Database;

/**
* AdPlus ad reset form.
*/
class AdPlusResetForm extends ConfirmFormBase {

  /**
   * The ID of the node to reset.
   *
   * @var string
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $page = \Drupal::request()->getRequestUri();
    if($page == '/admin/adplus/inactive/list') {
    	return 'adplus_reset_inactive_form';
    }
    else {
    	return 'adplus_reset_active_form';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
	$node = Node::load($this->id);
	$title = $node->get('title')->value;

    return t('Do you want to reset the impression and click count for this ad: %name?', array('%name' => $title));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $page = \Drupal::request()->getRequestUri();
    if($page == '/admin/adplus/inactive/list') {
      return new Url('adplus.ad_inactive_list');
    }
    else {
      return new Url('adplus.ad_list');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Reseting ad will reset the impressions and clicks count and history.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   *
   * @param int $id
   *   (optional) The ID of the node to be reset.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid = NULL) {
    $this->id = $nid;

    $page = \Drupal::request()->getRequestUri();

	//load the node
	$node = Node::load($nid);
	$type = $node->get('type')->target_id;

	//redirect if node type not matched
	if(!in_array($type, array('adplus_imagead', 'adplus_textad'))) {
	  if($page == '/admin/adplus/inactive/list') {
	  	$url = Url::fromRoute('adplus.ad_inactive_list');
	  	return new RedirectResponse($url->toString());
	  }
	  else {
	  	$url = Url::fromRoute('adplus.ad_list');
	  	return new RedirectResponse($url->toString());
	  }
	}

	//set the redirection type
    if($page == '/admin/adplus/inactive/list') {
	  $form['redirect_type'] = array('#type'=>'hidden', '#value' => 'InActive');
    }
    else {
	  $form['redirect_type'] = array('#type'=>'hidden', '#value' => 'Active');
    }

	//don't cache
    \Drupal::service('page_cache_kill_switch')->trigger();

    return parent::buildForm($form, $form_state);
  }

  /**
  * {@inheritdoc}
  */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	//reset impression
	$node = Node::load($this->id);
	$node->field_ad_impression->value = 0;
	$node->save();

	//reset clicks history
	$sql  = "DELETE FROM {adplus_clicks} ";
	$sql .= "WHERE entity_id=:entity_id ";

	$values = array(
	  ':entity_id' => $this->id,
	);
	Database::getConnection()->query($sql, $values);

	drupal_set_message($this->t('Ad has been reset successfully.'));

	if($form_state->getValue('redirect_type')=='InActive') {
	  $form_state->setRedirect('adplus.ad_inactive_list');
	}
	else {
	  $form_state->setRedirect('adplus.ad_list');
	}
  }

}
?>
