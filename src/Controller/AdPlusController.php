<?php

namespace Drupal\adplus\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing;
use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Request;


/**
 * Controller routines for ad.
 */
class AdPlusController extends ControllerBase {

  /**
   * Redirect to ad url
   */
  public function redirect_ad(Request $request = null) {
	// GET
    //$data = $request->query->all();

    //get the fields by id
    $ad_id = $request->get('ad_id');
    $url = $request->get('url');

    //check if url is empty
	if(empty($url) || empty($ad_id)) {
	  $page_url = Url::fromRoute('entity.node.canonical', ['node' => 35]);
	  return new RedirectResponse($page_url->toString());
	}

	//Load node
	$node = Node::load($ad_id);
	$type = $node->get('type')->target_id;

    //check if url is empty
	if(empty($type)) {
	  $page_url = Url::fromRoute('entity.node.canonical', ['node' => 35]);
	  return new RedirectResponse($page_url->toString());
	}

  	$account = \Drupal::currentUser();

	//\Drupal::logger('ad')->notice($ad_id.' - '.$account->id().' - '.\Drupal::request()->getClientIp());

	$sql  = "INSERT INTO {adplus_clicks} (entity_id, entity_type, uid, clicks, ip_address, timestamp) VALUES ";
	$sql .= "(:entity_id, :entity_type, :uid, :clicks, :ip_address, :timestamp) ";
	$sql .= "ON DUPLICATE KEY UPDATE clicks = clicks+1, timestamp =".time();

	$values = array(
		':entity_id' => $ad_id,
		':entity_type' => $type,
		':uid' => $account->id(),
		':clicks' => 1,
		':ip_address' => \Drupal::request()->getClientIp(),
		':timestamp' => time(),
	);
	//db_query($sql, $values);
	Database::getConnection()->query($sql, $values);

	//don't cache
    \Drupal::service('page_cache_kill_switch')->trigger();

    //redirect to ad url
	if(!empty($url)) {
	  return new \Drupal\Core\Routing\TrustedRedirectResponse($url);
	}

	//it should never reach here
    $build = array(
      '#markup' => '',
    );

    return $build;
  }

}
