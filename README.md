# Drupal Advertise module:
Display local advertisement in your site


Overview:
--------
AdPlus is a small advertisement module which provides two type of content types:
ImageAd and TextAd. You can create purely image type ad or html type ad. Based on
Ad location selection, it will display randomly ad on the ad block. You can create
as many as "ad locations" from taxonomy.

For image type of ad, if you upload more then one image, it will display as slideshow
based on jquery cycle 2 plugin (http://jquery.malsup.com/cycle2/download/).


* http://jquery.malsup.com/cycle2/download/



Features:
---------

The AdPlus module:

* Provides two ad content types: ImageAd and TextAd.
* Facilitate to create "ad locations" from taxonomy
* Provides report on impression count and click count and click history.
* Active and InActive ads are separated by tabs.
* Drush command, drush colorbox-plugin, to download and install the AdPlus plugin.

Installation:
------------
1. Install the module as normal, see link for instructions.
   Link: https://www.drupal.org/documentation/install/modules-themes/modules-8
2. Download and unpack the jQuery Cycle 2 plugin from (http://jquery.malsup.com/cycle2/download/) 
	and place it under "js" folder under the module directory.
   Link: http://malsup.github.io/min/jquery.cycle2.min.js
   Drush users can use the command "drush adplus-plugin".
3. Go to "Administer" -> "Extend" and enable the AdPlus module.
4. See the "Issues" section before installation.

Configuration:
-------------
There is no module wise settings. While you add the block
to any region there are two settings you need to seect: 
"Ad Location" and "Ad Type". Based on these two settings
ad content will be filtered for that block display.

Issues:
------------
* https://www.drupal.org/node/2788087

While installing the module, you may face this error:
Uncaught PHP Exception Symfony\\Component\\DependencyInjection\\Exception\\ServiceNotFoundException: "You have requested a non-existent service "router.route_provider.old". 
Did you mean one of these: "router.route_provider", "router.route_preloader"?" at ..\\core\\lib\\Drupal\\Component\\DependencyInjection\\Container.php line 157

A temporary solution is, open the core/core.services.yml file in a text editor and copy the routing info from "router.route_provider" and
create and rename it as "router.route_provider.old". You can later delete this section.

Or

You can apply the patches provides there.

Example:
------------
<img src="long_ad1.jpg" alt="Long Ad" /><br>

<img src="long_ad2.jpg" alt="Long Ad" /><br>

<img src="short_ad1.jpg" alt="Short Ad" />


---------------------------------------------------------------
You can contact me at: <strong>Shafiq Hossain</strong>, <em>md.shafiq.hossain@gmail.com</em>

