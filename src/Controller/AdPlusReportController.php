<?php

namespace Drupal\adplus\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query;
Use Drupal\Core\Routing;

/**
 * Controller routines for adplus.
 */
class AdPlusReportController extends ControllerBase {

  /**
   * Display list of ads
   */
  public function ad_list() {
    $content = array();

    $content['form'] = \Drupal::formBuilder()->getForm('Drupal\adplus\Form\AdPlusFilterForm');

    $headers = array(
      t('Id'),
      t('Title'),
      t('Ad Location'),
      t('Ad Type'),
      t('Client Name'),
      t('Start Date'),
      t('End Date'),
      t('Status'),
      t('Impressions'),
      t('Clicks'),
      t('Links'),
    );


	$query = \Drupal::database()->select('node_field_data', 'n');
	$query->fields('n', ['nid', 'title', 'type']);

	$query->join('node__field_ad_location', 'a', 'n.nid = a.entity_id');
	$query->addField('a', 'field_ad_location_target_id');
	$query->join('node__field_ad_type', 'b', 'n.nid = b.entity_id');
	$query->addField('b', 'field_ad_type_value');
	$query->join('node__field_client_name', 'c', 'n.nid = c.entity_id');
	$query->addField('c', 'field_client_name_value');
	$query->join('node__field_ad_impression', 'd', 'n.nid = d.entity_id');
	$query->addField('d', 'field_ad_impression_value');
	$query->join('node__field_start_date', 'e', 'n.nid = e.entity_id');
	$query->addField('e', 'field_start_date_value');
	$query->join('node__field_end_date', 'f', 'n.nid = f.entity_id');
	$query->addField('f', 'field_end_date_value');
	$query->join('node__field_active', 'g', 'n.nid = g.entity_id');
	$query->addField('g', 'field_active_value');

	$query->condition('n.type', array('adplus_imagead', 'adplus_textad'), 'IN');

	//node title
	if(isset($_SESSION['ad_ad_title']) && !empty($_SESSION['ad_ad_title'])) {
	  $query->condition('n.title', '%' . $query->escapeLike($_SESSION['ad_ad_title']) . '%', 'LIKE');
	}

	//client name
	if(isset($_SESSION['ad_client_name']) && !empty($_SESSION['ad_client_name'])) {
	  $query->condition('c.field_client_name_value', '%' . $query->escapeLike($_SESSION['ad_client_name']) . '%', 'LIKE');
	}

	//page location
	if(isset($_SESSION['ad_ad_location']) && !empty($_SESSION['ad_ad_location'])) {
	  $query->condition('a.field_ad_location_target_id', $_SESSION['ad_ad_location'], '=');
	}

	//ad type
	if(isset($_SESSION['ad_ad_type']) && !empty($_SESSION['ad_ad_type'])) {
	  $query->condition('b.field_ad_type_value', $_SESSION['ad_ad_type'], '=');
	}

	//start date
	if(isset($_SESSION['ad_start_date']) && !empty($_SESSION['ad_start_date'])) {
	  $query->condition('e.field_start_date_value', $_SESSION['ad_start_date'], '=');
	}

	//end date
	if(isset($_SESSION['ad_end_date']) && !empty($_SESSION['ad_end_date'])) {
	  $query->condition('f.field_end_date_value', $_SESSION['ad_end_date'], '=');
	}

	$query->condition('g.field_active_value', 1, '=');

 	$table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($headers);
	$pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);
	$results = $pager->execute()->fetchAll();

    $rows = array();
	foreach($results as $row) {
		if(isset($row->field_ad_location_target_id) && !empty($row->field_ad_location_target_id)) {
	  	  $term = \Drupal\taxonomy\Entity\Term::load($row->field_ad_location_target_id);
		  $ad_location = $term->name->value;
		}
		else {
		  $ad_location = '-';
		}

		$ad_type = (isset($row->field_ad_type_value) && $row->field_ad_type_value=='long' ? 'Long' : (isset($row->field_ad_type_value) && $row->field_ad_type_value=='medium' ? 'Medium' : 'Short'));
		$status = (isset($row->field_active_value) && $row->field_active_value==1 ? 'Active' : 'In-Active');

		$clicks = $this->get_total_ad_clicks($row->nid);

		$links = '';
		$links .= Link::fromTextAndUrl(t('In-Active'), Url::fromRoute('adplus.ad_inactive', ['nid' => $row->nid], ['query' => ['destination' => '/admin/adplus/list'], 'attributes' => ['class' => ['ad-inactive']] ]))->toString();
		$links .= '&nbsp;|&nbsp;';
		$links .= Link::fromTextAndUrl(t('Edit'), Url::fromUri('internal:/node/'.$row->nid.'/edit', ['query' => ['destination' => '/admin/adplus/list'], 'attributes' => ['class' => ['ad-edit']] ]))->toString();
		$links .= '&nbsp;|&nbsp;';
		$links .= Link::fromTextAndUrl(t('Reset'), Url::fromRoute('adplus.ad_reset', ['nid' => $row->nid], ['query' => ['destination' => '/admin/adplus/list'], 'attributes' => ['class' => ['ad-reset']] ]))->toString();
		$links .= '&nbsp;|&nbsp;';
		$links .= Link::fromTextAndUrl(t('Delete'), Url::fromRoute('adplus.ad_delete', ['nid' => $row->nid], ['query' => ['destination' => '/admin/adplus/list'], 'attributes' => ['class' => ['ad-delete']] ]))->toString();

		$rows[] = array(
					'data' => array(
						  $row->nid,
						  $row->title,
						  $ad_location,
						  $ad_type,
						  !empty($row->field_client_name_value) ? $row->field_client_name_value : '',
						  !empty($row->field_start_date_value) ? $row->field_start_date_value : '',
						  !empty($row->field_end_date_value) ? $row->field_end_date_value : '',
						  $status,
						  !empty($row->field_ad_impression_value) ? $row->field_ad_impression_value : 0,
						  Link::fromTextAndUrl($clicks, Url::fromRoute('adplus.ad_clicks_list', ['nid' => $row->nid], ['query' => ['destination' => '/admin/adplus/list'], 'attributes' => ['class' => ['ad-clicks'], 'target' => ['_blank']] ]))->toString(),
						  \Drupal\Core\Render\Markup::create($links),
					)
		);
	}

    $content['table'] = array(
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => array('id' => 'adplus-ad-list'),
      '#empty' => t('Sorry! No active ad records found.'),
    );

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }


  /**
   * Display list of inactive ads
   */
  public function ad_inactive_list() {
    $content = array();

    $content['form'] = \Drupal::formBuilder()->getForm('Drupal\adplus\Form\AdPlusFilterForm');

    $headers = array(
      t('Id'),
      t('Title'),
      t('Ad Location'),
      t('Ad Type'),
      t('Client Name'),
      t('Start Date'),
      t('End Date'),
      t('Status'),
      t('Impressions'),
      t('Clicks'),
      t('Links'),
    );


	$query = \Drupal::database()->select('node_field_data', 'n');
	$query->fields('n', ['nid', 'title', 'type']);

	$query->join('node__field_ad_location', 'a', 'n.nid = a.entity_id');
	$query->addField('a', 'field_ad_location_target_id');
	$query->join('node__field_ad_type', 'b', 'n.nid = b.entity_id');
	$query->addField('b', 'field_ad_type_value');
	$query->join('node__field_client_name', 'c', 'n.nid = c.entity_id');
	$query->addField('c', 'field_client_name_value');
	$query->join('node__field_ad_impression', 'd', 'n.nid = d.entity_id');
	$query->addField('d', 'field_ad_impression_value');
	$query->join('node__field_start_date', 'e', 'n.nid = e.entity_id');
	$query->addField('e', 'field_start_date_value');
	$query->join('node__field_end_date', 'f', 'n.nid = f.entity_id');
	$query->addField('f', 'field_end_date_value');
	$query->join('node__field_active', 'g', 'n.nid = g.entity_id');
	$query->addField('g', 'field_active_value');

	$query->condition('n.type', array('adplus_imagead', 'adplus_textad'), 'IN');

	//node title
	if(isset($_SESSION['ad_ad_title']) && !empty($_SESSION['ad_ad_title'])) {
	  $query->condition('n.title', '%' . $query->escapeLike($_SESSION['ad_ad_title']) . '%', 'LIKE');
	}

	//client name
	if(isset($_SESSION['ad_client_name']) && !empty($_SESSION['ad_client_name'])) {
	  $query->condition('c.field_client_name_value', '%' . $query->escapeLike($_SESSION['ad_client_name']) . '%', 'LIKE');
	}

	//page location
	if(isset($_SESSION['ad_ad_location']) && !empty($_SESSION['ad_ad_location'])) {
	  $query->condition('a.field_ad_location_target_id', $_SESSION['ad_ad_location'], '=');
	}

	//ad type
	if(isset($_SESSION['ad_ad_type']) && !empty($_SESSION['ad_ad_type'])) {
	  $query->condition('b.field_ad_type_value', $_SESSION['ad_ad_type'], '=');
	}

	//start date
	if(isset($_SESSION['ad_start_date']) && !empty($_SESSION['ad_start_date'])) {
	  $query->condition('e.field_start_date_value', $_SESSION['ad_start_date'], '=');
	}

	//end date
	if(isset($_SESSION['ad_end_date']) && !empty($_SESSION['ad_end_date'])) {
	  $query->condition('f.field_end_date_value', $_SESSION['ad_end_date'], '=');
	}

	$query->condition('g.field_active_value', 0, '=');

 	$table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($headers);
	$pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);
	$results = $pager->execute()->fetchAll();

    $rows = array();
	foreach($results as $row) {
		if(isset($row->field_ad_location_target_id) && !empty($row->field_ad_location_target_id)) {
	  	  $term = \Drupal\taxonomy\Entity\Term::load($row->field_ad_location_target_id);
		  $ad_location = $term->name->value;
		}
		else {
		  $ad_location = '-';
		}

		$ad_type = (isset($row->field_ad_type_value) && $row->field_ad_type_value=='long' ? 'Long' : (isset($row->field_ad_type_value) && $row->field_ad_type_value=='medium' ? 'Medium' : 'Short'));
		$status = (isset($row->field_active_value) && $row->field_active_value==1 ? 'Active' : 'In-Active');

		$clicks = $this->get_total_ad_clicks($row->nid);

		$links = '';
		$links .= Link::fromTextAndUrl(t('Active'), Url::fromRoute('adplus.ad_active', ['nid' => $row->nid], ['query' => ['destination' => '/admin/adplus/inactive/list'], 'attributes' => ['class' => ['ad-active']] ]))->toString();
		$links .= '&nbsp;|&nbsp;';
		$links .= Link::fromTextAndUrl(t('Edit'), Url::fromUri('internal:/node/'.$row->nid.'/edit', ['query' => ['destination' => '/admin/adplus/inactive/list'], 'attributes' => ['class' => ['ad-edit']] ]))->toString();
		$links .= '&nbsp;|&nbsp;';
		$links .= Link::fromTextAndUrl(t('Reset'), Url::fromRoute('adplus.ad_reset', ['nid' => $row->nid], ['query' => ['destination' => '/admin/adplus/inactive/list'], 'attributes' => ['class' => ['ad-reset']] ]))->toString();
		$links .= '&nbsp;|&nbsp;';
		$links .= Link::fromTextAndUrl(t('Delete'), Url::fromRoute('adplus.ad_delete', ['nid' => $row->nid], ['query' => ['destination' => '/admin/adplus/inactive/list'], 'attributes' => ['class' => ['ad-delete']] ]))->toString();

		$rows[] = array(
					'data' => array(
						  $row->nid,
						  $row->title,
						  $ad_location,
						  $ad_type,
						  !empty($row->field_client_name_value) ? $row->field_client_name_value : '',
						  !empty($row->field_start_date_value) ? $row->field_start_date_value : '',
						  !empty($row->field_end_date_value) ? $row->field_end_date_value : '',
						  $status,
						  !empty($row->field_ad_impression_value) ? $row->field_ad_impression_value : 0,
						  Link::fromTextAndUrl($clicks, Url::fromRoute('adplus.ad_clicks_list', ['nid' => $row->nid], ['query' => ['destination' => '/admin/adplus/inactive/list'], 'attributes' => ['class' => ['ad-clicks'], 'target' => ['_blank']] ]))->toString(),
						  \Drupal\Core\Render\Markup::create($links),
					)
		);
	}

    $content['table'] = array(
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => array('id' => 'adplus-inactive-ad-list'),
      '#empty' => t('Sorry! No inactive ad records found.'),
    );

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }

  /**
   * Display ad clicks detail
   */
  public function ad_clicks_list($nid) {
    //check if nid is empty
	if(empty($nid)) {
	  drupal_set_message($this->t('No ad reference provided.'));
	  return new RedirectResponse('/admin/adplus/list');
	}

    $content = array();

    $headers = array(
      t('Id'),
      t('Title'),
      t('User'),
      t('Clicks'),
      t('IP Address'),
      t('Last Update'),
    );


	$query = \Drupal::database()->select('adplus_clicks', 'ad');
	$query->fields('ad', ['uid', 'clicks', 'ip_address', 'timestamp']);

	$query->join('node_field_data', 'n', 'n.nid = ad.entity_id');
	$query->fields('n', ['nid', 'title', 'type']);
	$query->join('users_field_data', 'u', 'ad.uid = u.uid');
	$query->addField('u', 'name');

	$query->condition('n.type', array('adplus_imagead', 'adplus_textad'), 'IN');
	$query->condition('n.nid', $nid, '=');

 	$table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($headers);
	$pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);
	$results = $pager->execute()->fetchAll();

    $rows = array();
	foreach($results as $row) {
		$rows[] = array(
					'data' => array(
						  $row->nid,
						  $row->title,
						  !empty($row->name) ? $row->name : 'Anonymous',
						  $row->clicks,
						  !empty($row->ip_address) ? $row->ip_address : '',
						  !empty($row->timestamp) ? date('Y-m-d', $row->timestamp) : '',
					)
		);
	}

    $content['table'] = array(
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => array('id' => 'adplus-ad-clicks-list'),
      '#empty' => t('Sorry! No ad clicks records found.'),
    );

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }

  /**
   * Count total clicks on an Ad
   */
  private function get_total_ad_clicks($nid) {
	$query = \Drupal::database()->select('adplus_clicks', 'n');
	$query->addExpression("sum(clicks)", "total_clicks");
	$query->groupBy('n.entity_id');
	$query->condition('n.entity_id', $nid, '=');

	$results = $query->execute()->fetchAll();

	$total_clicks = 0;
	foreach($results as $row) {
	  $total_clicks = $row->total_clicks;
	}

	return $total_clicks;
  }

  /**
   * AdPlus mark Active
   */
  public function ad_active(Request $request = null) {
    //get the fields by id
    $destination = $request->get('destination');
	$parameters = \Drupal::routeMatch()->getParameters();
	$nid = $parameters->get('nid');

    //check if nid is empty
	if(empty($nid)) {
	  if(!empty($destination)) {
	    $page_url = Url::fromUri('internal:'.$destination);
	    return new RedirectResponse($page_url->toString());
	  }
	  else {
	    $page_url = Url::fromRoute('adplus.ad_list');
	    return new RedirectResponse($page_url->toString());
	  }
	}

	//Load node
	$node = Node::load($nid);
	$type = $node->get('type')->target_id;

    //check if url is empty
	if(empty($type) || !in_array($type, array('adplus_imagead','adplus_textad'))) {
	  if(!empty($destination)) {
	    $page_url = Url::fromUri('internal:'.$destination);
	    return new RedirectResponse($page_url->toString());
	  }
	  else {
	    $page_url = Url::fromRoute('adplus.ad_list');
	    return new RedirectResponse($page_url->toString());
	  }
	}

	//save the node
	$node->field_active->value = 1;
	$node->save();

	drupal_set_message($this->t('Ad has been activeted successfully.'));

	//don't cache
    \Drupal::service('page_cache_kill_switch')->trigger();

	//now redirect
	if(!empty($destination)) {
	  $page_url = Url::fromUri('internal:'.$destination);
	  return new RedirectResponse($page_url->toString());
	}
	else {
	  $page_url = Url::fromRoute('adplus.ad_list');
	  return new RedirectResponse($page_url->toString());
	}

	//it should never reach here
    $build = array(
      '#markup' => '',
    );

    return $build;
  }

  /**
   * AdPlus mark InActive
   */
  public function ad_inactive(Request $request = null) {
    //get the fields by id
    $destination = $request->get('destination');
	$parameters = \Drupal::routeMatch()->getParameters();
	$nid = $parameters->get('nid');

    //check if nid is empty
	if(empty($nid)) {
	  if(!empty($destination)) {
	    $page_url = Url::fromUri('internal:'.$destination);
	    return new RedirectResponse($page_url->toString());
	  }
	  else {
	    $page_url = Url::fromRoute('adplus.ad_list');
	    return new RedirectResponse($page_url->toString());
	  }
	}

	//Load node
	$node = Node::load($nid);
	$type = $node->get('type')->target_id;

    //check if url is empty
	if(empty($type) || !in_array($type, array('adplus_imagead','adplus_textad'))) {
	  if(!empty($destination)) {
	    $page_url = Url::fromUri('internal:'.$destination);
	    return new RedirectResponse($page_url->toString());
	  }
	  else {
	    $page_url = Url::fromRoute('adplus.ad_list');
	    return new RedirectResponse($page_url->toString());
	  }
	}

	//save the node
	$node->field_active->value = 0;
	$node->save();

	drupal_set_message($this->t('Ad has been de-activeted successfully.'));

	//don't cache
    \Drupal::service('page_cache_kill_switch')->trigger();

	//now redirect
	if(!empty($destination)) {
	  $page_url = Url::fromUri('internal:'.$destination);
	  return new RedirectResponse($page_url->toString());
	}
	else {
	  $page_url = Url::fromRoute('adplus.ad_list');
	  return new RedirectResponse($page_url->toString());
	}

	//it should never reach here
    $build = array(
      '#markup' => '',
    );

    return $build;
  }


}
