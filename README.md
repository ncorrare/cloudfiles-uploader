cloudfiles-uploader
===================

just a proof of concept on how to use cloudfiles, temporary url's, streaming, etc ... THIS IS NOT FOR PRODUCTION USE, its just a test, or some working code examples for you to build your application on it.

Requires:

-php-cloudfiles (https://github.com/rackspace/php-cloudfiles/)
-jwplayer (www.longtailvideo.com)

JW Player is used for the Stream Button... I'm going to write some other thing based on HTML 5, also based on mime content/type (from Cloudfiles).
JW Player is not free (as in water), so I'm not bundling it, you can download it yourself for free (as in beer) for personal use.

I'm also using a MySQL database just save some description on the file, but you could use cloudfiles as well for that.

That database needs only one table:

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fname` varchar(80) NOT NULL,
  `desc` varchar(80) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--

The variables are documented on the top of the code.

Another thing you need is an "uploads/" folder, just for temporary data.
