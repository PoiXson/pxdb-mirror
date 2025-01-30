<?php declare(strict_types=1);
/*
 * PoiXson pxdb - PHP Database Utilities Library
 * @copyright 2004-2025
 * @license AGPLv3+ADD-PXN-V1
 * @author lorenzo at poixson.com
 * @link https://poixson.com/
 */
namespace pxn\pxdb\tests;

use \pxn\pxdb\dbPool;
use \pxn\phpUtils\xPaths;


require('vendor/autoload.php');


dbPool::LoadAll(xPaths::common().'/tests');
