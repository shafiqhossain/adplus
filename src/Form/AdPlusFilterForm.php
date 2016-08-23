<?php
/**
* @file
* Contains \Drupal\adplus\Form\AdPlusFilterForm.
*/

namespace Drupal\adplus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;


/**
* AdPlus filter form.
*/
class AdPlusFilterForm extends FormBase {
  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    $page = \Drupal::request()->getRequestUri();
    if($page == '/admin/adplus/inactive/list') {
    	return 'adplus_filter_inactive_form';
    }
    else {
    	return 'adplus_filter_active_form';
    }
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {

	$form['ad_title'] = array(
	  '#type' => 'textfield',
	  '#title' => $this->t('Title'),
	  '#size' => 40,
	  '#maxlength' => 255,
	  '#default_value' => isset($_SESSION['ad_ad_title']) ? $_SESSION['ad_ad_title'] : '',
	  '#placeholder' => $this->t('Enter the ad title'),
	);
	$form['client_name'] = array(
	  '#type' => 'textfield',
	  '#title' => $this->t('Client Name'),
	  '#size' => 40,
	  '#maxlength' => 255,
	  '#default_value' => isset($_SESSION['ad_client_name']) ? $_SESSION['ad_client_name'] : '',
	  '#placeholder' => $this->t('Enter the client name'),
	);

    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', "adplus_ad_location");
    $tids = $query->execute();
    $options = array();
    $options[] = $this->t('Any');
    if(count($tids)>0) {
      foreach($tids as $tid) {
        $term = \Drupal\taxonomy\Entity\Term::load($tid);
        $options[$term->tid] = $term->name;
      }
    }
	$form['ad_location'] = [
	  '#type' => 'select',
	  '#title' => $this->t('Ad Location'),
	  '#options' => $options,
	  '#default_value' => isset($_SESSION['ad_ad_location']) ? $_SESSION['ad_ad_location'] : '',
	];
	$form['ad_type'] = [
	  '#type' => 'select',
	  '#title' => $this->t('Ad Type'),
	  '#options' => [
		'' => $this->t('Any'),
		'long' => $this->t('Long'),
		'medium' => $this->t('Medium'),
		'short' => $this->t('Short'),
	  ],
	  '#default_value' => isset($_SESSION['ad_ad_type']) ? $_SESSION['ad_ad_type'] : '',
	];
	$form['start_date'] = array(
	  '#type' => 'textfield',
	  '#title' => $this->t('Start Date'),
	  '#default_value' => isset($_SESSION['ad_start_date']) ? $_SESSION['ad_start_date'] : '',
	  '#size' => 30,
	  '#maxlength' => 50,
	  '#placeholder' => $this->t('Enter the Ad start date'),
	);
	$form['end_date'] = array(
	  '#type' => 'textfield',
	  '#title' => $this->t('End Date'),
	  '#default_value' => isset($_SESSION['ad_end_date']) ? $_SESSION['ad_end_date'] : '',
	  '#size' => 30,
	  '#maxlength' => 50,
	  '#placeholder' => $this->t('Enter the Ad end date'),
	);

    $page = \Drupal::request()->getRequestUri();
    if($page == '/admin/adplus/inactive/list') {
	  $form['redirect_type'] = array('#type'=>'hidden', '#value' => 'InActive');
    }
    else {
	  $form['redirect_type'] = array('#type'=>'hidden', '#value' => 'Active');
    }

	$form['submit'] = array(
	  '#type' => 'submit',
	  '#value' => $this->t('Search'),
	  '#id' => 'edit-submit',
	);

    return $form;
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
	$_SESSION['ad_ad_title'] =  $form_state->getValue('ad_title');
	$_SESSION['ad_client_name'] =  $form_state->getValue('client_name');
	$_SESSION['ad_ad_location'] =  $form_state->getValue('ad_location');
	$_SESSION['ad_ad_type'] =  $form_state->getValue('ad_type');
	$_SESSION['ad_start_date'] =  $form_state->getValue('start_date');
	$_SESSION['ad_end_date'] =  $form_state->getValue('end_date');

	if($form_state->getValue('redirect_type')=='InActive') {
	  $form_state->setRedirect('adplus.ad_inactive_list');
	}
	else {
	  $form_state->setRedirect('adplus.ad_list');
	}
  }
}
?>
