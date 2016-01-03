<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\ProductPhoto;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use MongoDB\Driver\Manager;

class ScrappingController extends Controller
{
    public function getIndex()
    {
        set_time_limit(10000);

        $client 	= new Client();
        // $manager    = new Manager();

		// $crawler 	= $client->request('GET' , 'http://www.xsmlfashion.com/collection/women/');
		// $crawler 	= $client->request('GET' , 'http://amblefootwear.com/Products.aspx?id=20');
		// $crawler 	= $client->request('GET' , 'http://www.13thshoes.com/product-category/heels-wedges/page/4/');

		$products 	= [];
        $template_replaced 	  = '{{page}}';
        $template_urls[]       = 'http://www.13thshoes.com/product-category/heels-wedges/page/{{page}}/';
		$template_urls[]       = 'http://www.13thshoes.com/product-category/flatshoes/standard/page/{{page}}/';
        $template_urls[]       = 'http://www.13thshoes.com/product-category/flatshoes/boat/page/{{page}}/';
        $template_urls[]       = 'http://www.13thshoes.com/product-category/flatshoes/sandals/page/{{page}}/';
        $template_urls[]       = 'http://www.13thshoes.com/product-category/flatshoes/boots/page/{{page}}/';
        $template_urls[]       = 'http://www.13thshoes.com/product-category/heels-wedges/flatform/page/{{page}}/';
        $template_urls[]       = 'http://www.13thshoes.com/product-category/heels-wedges/heels/page/{{page}}/';
        $template_urls[]       = 'http://www.13thshoes.com/product-category/heels-wedges/wedges/page/{{page}}/';
        $template_urls[]       = 'http://www.13thshoes.com/product-category/accessories/clothes/page/{{page}}/';
        $template_urls[]       = 'http://www.13thshoes.com/product-category/accessories/bags/page/{{page}}/';


        foreach ($template_urls as $key => $template_url)
        {
    		for( $i=1 ; $i<=5 ; $i++ )
    		{
    			$url 		= str_replace($template_replaced,$i,$template_url);
    			$header_url	= get_headers($url, 1);
    			if($header_url[0] == 'HTTP/1.0 404 Not Found') break;

    			$crawler 	= $client->request('GET' , $url);

    			$products_crawler 	= $crawler->filter('.product-grid');

    			foreach ($products_crawler as $key => $product_crawler)
    			{
    				$node 	= new Crawler($product_crawler);
    				$url_page_link	= $node->filter('.product-image')->attr('href');
    				$page_crawler	= $client->request('GET', $url_page_link);

    				$name 			= $page_crawler->filter('.product-shop h1')->text();
    				$price 			= $page_crawler->filter('.price-block span')->text();


    				$images 		= [];
    				$images_obj 	= $page_crawler->filter('.views-gallery li a');

    				foreach($images_obj as $image_obj)
    				{
    					$images[] 	= $image_obj->getAttribute('href');
    				}

                    $sizes          = [];
                    $sizes_obj      = $page_crawler->filter('#pa_size option');
                    foreach ($sizes_obj as $key => $size_obj)
                    {
                        if($size_obj->textContent !== 'Choose size') $sizes[] = $size_obj->textContent;
                    }

                    $colors          = [];
                    $colors_obj      = $page_crawler->filter('#pa_color option');
                    foreach ($colors_obj as $key => $color_obj)
                    {
                        if($color_obj->textContent !== 'Choose color') $colors[] = $color_obj->textContent;
                    }


                    $tags          = [];
                    $tags_obj      = $page_crawler->filter('.tagged_as a');
                    foreach ($tags_obj as $key => $tag_obj)
                    {
                        if($tag_obj->textContent !== '13thshoes') $tags[] = $tag_obj->textContent;
                    }

                    $categories          = [];
                    $categories_obj      = $page_crawler->filter('.posted_in a');
                    foreach ($categories_obj as $key => $category_obj)
                    {
                        $categories[] = $category_obj->textContent;
                    }

                    $product            = new Product;
                    $product->shop_id   = 228;
                    $product->title     = $name;
                    $product->color     = json_encode($colors);
                    $product->size      = json_encode($sizes);
                    $product->tags      = json_encode($tags);
                    $product->category  = json_encode($categories);
                    $product->price     = filter_var($price, FILTER_SANITIZE_NUMBER_INT);
                    $product->url       = $url_page_link;
                    $product->save();

                    foreach ($images as $key => $image)
                    {
                        $photo                  = new ProductPhoto;
                        $photo->photo_url       = $image;
                        $photo->thumbnail_url   = $image;
                        $photo->product_id      = $product->id;
                        $photo->save();
                    }

    				$products[] 	= [
    						'name'		=> $name,
    						'price'		=> $price,
    						'images'	=> $images
    					];
    			}

    		}
        }

		return $products;
    }

    public function getXml()
    {
        set_time_limit(10000);

        $client 	= new Client();
        $template_replaced 	  = '{{page}}';

        $template_urls  = [
            "http://www.xsmlfashion.com/collection/collections/women/dresses/page/{{page}}",
            "http://www.xsmlfashion.com/collection/collections/women/bottoms-women/page/{{page}}",
            "http://www.xsmlfashion.com/collection/collections/women/outerwear-women/page/{{page}}",
            "http://www.xsmlfashion.com/collection/collections/women/accessoires-women/page/{{page}}",
            "http://www.xsmlfashion.com/collection/collections/women/bags-women/page/{{page}}",
            "http://www.xsmlfashion.com/collection/collections/women/shoes-women/page/{{page}}",
            "http://www.xsmlfashion.com/collection/collections/men/tops-men/page/{{page}}",
            "http://www.xsmlfashion.com/collection/collections/men/bottoms-men/page/{{page}}",
            "http://www.xsmlfashion.com/collection/collections/men/outerwear-men/page/{{page}}",
            "http://www.xsmlfashion.com/collection/collections/men/accessories-men/page/{{page}}",
            "http://www.xsmlfashion.com/collection/collections/men/shoes-men/{{page}}" ];

        foreach ($template_urls as $key => $template_url)
        {
            for( $i=1 ; $i<=5 ; $i++ )
            {
                $url 		= str_replace($template_replaced,$i,$template_url);
                $header_url	= get_headers($url, 1);
                if($header_url[0] == 'HTTP/1.0 404 Not Found') break;

                $crawler 	= $client->request('GET' , $url);

                $products_crawler 	= $crawler->filter('.product-small');

                foreach ($products_crawler as $key => $product_crawler)
                {
                    $node 	= new Crawler($product_crawler);
                    $url_page_link	= $node->filter('a')->attr('href');
                    $page_crawler	= $client->request('GET', $url_page_link);

                    $name 			= $page_crawler->filter('.entry-title')->text();
                    $price 			= $page_crawler->filter('.price .amount')->text();

                    $images 		= [];
                    $images_obj 	= $page_crawler->filter('.slide.easyzoom > a');

                    foreach($images_obj as $image_obj)
                    {
                        $images[] 	= $image_obj->getAttribute('href');
                    }

                    $sizes          = [];
                    $sizes_obj      = $page_crawler->filter('#size option');
                    foreach ($sizes_obj as $key => $size_obj)
                    {
                        $size   = html_entity_decode($size_obj->textContent);
                        if($size !== 'Choose an option…') $sizes[] = $size;
                    }

                    $colors          = [];
                    $colors_obj      = $page_crawler->filter('#color option');
                    foreach ($colors_obj as $key => $color_obj)
                    {
                        if($color_obj->textContent !== 'Choose an option…') $colors[] = $color_obj->textContent;
                    }


                    $tags          = [];
                    $tags_obj      = $page_crawler->filter('.tagged_as a');
                    foreach ($tags_obj as $key => $tag_obj)
                    {
                        $tags[] = $tag_obj->textContent;
                    }

                    $categories          = [];
                    $categories_obj      = $page_crawler->filter('.posted_in a');
                    foreach ($categories_obj as $key => $category_obj)
                    {
                        $categories[] = $category_obj->textContent;
                    }

                    $product            = new Product;
                    $product->title     = $name;
                    $product->color     = json_encode($colors);
                    $product->size      = json_encode($sizes);
                    $product->tags      = json_encode($tags);
                    $product->category  = json_encode($categories);
                    $product->price     = filter_var($price, FILTER_SANITIZE_NUMBER_INT) / 100;
                    $product->url       = $url_page_link;
                    
                    $product->save();

                    foreach ($images as $key => $image)
                    {
                        $photo                  = new ProductPhoto;
                        $photo->photo_url       = $image;
                        $photo->thumbnail_url   = $image;
                        $photo->product_id      = $product->id;
                        $photo->save();
                    }

                    $products[] 	= [
                            'name'		=> $name,
                            'price'		=> $price,
                            'images'	=> $images
                        ];
                }

            }
        }
    }


    public function getAdorable()
    {
        set_time_limit(10000);

        $client 	= new Client();
        // $manager    = new Manager();

		// $crawler 	= $client->request('GET' , 'http://www.xsmlfashion.com/collection/women/');
		// $crawler 	= $client->request('GET' , 'http://amblefootwear.com/Products.aspx?id=20');
		// $crawler 	= $client->request('GET' , 'http://www.13thshoes.com/product-category/heels-wedges/page/4/');

		$products 	= [];
        $template_replaced 	  = '{{page}}';

        $template_urls = [
        	"http://adorableprojects.com/index.php?id_category=19&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=18&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=20&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=21&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=22&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=24&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=25&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=31&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=44&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=9&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=33&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=49&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=10&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=45&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=55&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=13&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=16&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=41&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=48&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=43&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=30&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=27&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=28&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=47&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=29&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=51&controller=category&id_lang=1&p={{page}}" ,
        	// "http://adorableprojects.com/index.php?id_category=54&controller=category&id_lang=1&p={{page}}"
         ];

        foreach ($template_urls as $key => $template_url)
        {
    		for( $i=1 ; $i<=5 ; $i++ )
    		{
    			$url 		= str_replace($template_replaced,$i,$template_url);
    			$header_url	= get_headers($url, 1);
    			if($header_url[0] == 'HTTP/1.0 404 Not Found') break;

    			$crawler 	= $client->request('GET' , $url);

    			$products_crawler 	= $crawler->filter('.ajax_block_product');

    			foreach ($products_crawler as $key => $product_crawler)
    			{
    				$node 	= new Crawler($product_crawler);
    				$url_page_link	= $node->filter('a.product_img_link')->attr('href');
    				$page_crawler	= $client->request('GET', $url_page_link);

    				$name 			= $page_crawler->filter('.bungkus h1')->text();
    				$price 			= $page_crawler->filter('#our_price_display')->text();

    				$images 		= [];
    				$images_obj 	= $page_crawler->filter('.thickbox');

    				foreach($images_obj as $image_obj)
    				{
    					$images[] 	= $image_obj->getAttribute('href');
    				}
    				// header(s'Content-Type: application/json');

                    $product        = new Product;
                    $product->title = $name;
                    $product->price = filter_var($price, FILTER_SANITIZE_NUMBER_INT) / 100;
                    $product->save();

                    foreach ($images as $key => $image)
                    {
                        $photo                  = new ProductPhoto;
                        $photo->photo_url       = $image;
                        $photo->thumbnail_url   = $image;
                        $photo->product_id      = $product->id;
                        $photo->save();
                    }

    				$products[] 	= [
    						'name'		=> $name,
    						'price'		=> $price,
    						'images'	=> $images
    					];
    			}

                $next_page      = $crawler->filter('.pagination_next.disabled');
                if(sizeof($next_page) < 1) break;

    		}
        }
    }

    public function getLove()
    {
        set_time_limit(10000);
        $base_url   = 'loveandflair.com';
        $client 	= new Client();
		$products 	= [];
        $template_replaced 	  = '{{page}}';

        $template_urls = [
            "http://loveandflair.com/collections/tops?page={{page}}",
            "http://loveandflair.com/collections/bottoms?page={{page}}",
            "http://loveandflair.com/collections/dresses?page={{page}}",
            "http://loveandflair.com/collections/rompers-jumpsuits?page={{page}}",
            "http://loveandflair.com/collections/accessories-1?page={{page}}",
            "http://loveandflair.com/collections/swimwear?page={{page}}",
        	"http://loveandflair.com/collections/accessories?page={{page}}",
         ];

        foreach ($template_urls as $key => $template_url)
        {
    		for( $i=1 ; $i<=1 ; $i++ )
    		{
    			$url 		= str_replace($template_replaced,$i,$template_url);
    			$header_url	= get_headers($url, 1);
    			if($header_url[0] == 'HTTP/1.0 404 Not Found') break;

    			$crawler 	= $client->request('GET' , $url);

    			$products_crawler 	= $crawler->filter('.reveal');

    			foreach ($products_crawler as $key => $product_crawler)
    			{
    				$node 	        = new Crawler($product_crawler);
    				$url_page_link	= $node->filter('a')->attr('href');

    				$page_crawler	= $client->request('GET', $url_page_link);
                    echo $url_page_link;

                    $name 			= $page_crawler->filter('#product-description h1')->text();
                    $price 			= $page_crawler->filter('span.product-price')->text();

                    echo $price;
    				$images 		= [];
    				$images_obj 	= $page_crawler->filter('.mthumb img');

    				foreach($images_obj as $image_obj)
    				{
    					$images[] 	= $image_obj->getAttribute('src');
    				}
    				// header(s'Content-Type: application/json');

                    $product            = new Product;
                    $product->shop_id   = 250;
                    $product->title     = $name;
                    $product->url       = $url_page_link;
                    $product->price     = filter_var($price, FILTER_SANITIZE_NUMBER_INT) / 100;

                    $product->save();

                    foreach ($images as $key => $image)
                    {
                        $photo                  = new ProductPhoto;
                        $photo->photo_url       = $image;
                        $photo->thumbnail_url   = $image;
                        $photo->product_id      = $product->id;
                        $photo->save();
                    }

    				$products[] 	= [
    						'name'		=> $name,
    						'price'		=> $price,
    						'images'	=> $images
    					];
    			}

                $next_page      = $crawler->filter('.pagination_next.disabled');
                if(sizeof($next_page) < 1) break;

    		}
        }
    }

    public function getAnynome()
    {
        set_time_limit(10000);
        $base_url   = 'http://anynome.com';
        $client 	= new Client();
		$products 	= [];
        $template_replaced 	  = '{{page}}';


        $template_urls  = [
            "www.anynome.com/dresses.html",
    		"www.anynome.com/tops.html",
            "www.anynome.com/jackets.html",
            "www.anynome.com/pants.html",
            "www.anynome.com/skirts.html",
            "www.anynome.com/shorts.html",
            "www.anynome.com/lookbook.html"
        ];

        foreach ($template_urls as $key => $template_url)
        {
    		for( $i=1 ; $i<=1 ; $i++ )
    		{
    			$url 		= "http://" . str_replace($template_replaced,$i,$template_url);
    			$header_url	= get_headers($url, 1);
    			if($header_url[0] == 'HTTP/1.0 404 Not Found') break;

    			$crawler 	= $client->request('GET' , $url);

    			$products_crawler 	= $crawler->filter('.wsite-image.wsite-image-border-none a');

    			foreach ($products_crawler as $key => $product_crawler)
    			{
    				$node 	        = new Crawler($product_crawler);
    				$url_page_link	= $base_url . $node->filter('a')->attr('href');
                    echo $url_page_link;
                    print_r(get_meta_tags($url_page_link));
                    exit;

                    $name 			= $page_crawler->filter('#product-description h1')->text();
                    $price 			= $page_crawler->filter('span.product-price')->text();

                    echo $price;
    				$images 		= [];
    				$images_obj 	= $page_crawler->filter('.mthumb img');

    				foreach($images_obj as $image_obj)
    				{
    					$images[] 	= $image_obj->getAttribute('src');
    				}
    				// header(s'Content-Type: application/json');

                    $product            = new Product;
                    $product->shop_id   = 250;
                    $product->title     = $name;
                    $product->url       = $url_page_link;
                    $product->price     = filter_var($price, FILTER_SANITIZE_NUMBER_INT) / 100;

                    $product->save();

                    foreach ($images as $key => $image)
                    {
                        $photo                  = new ProductPhoto;
                        $photo->photo_url       = $image;
                        $photo->thumbnail_url   = $image;
                        $photo->product_id      = $product->id;
                        $photo->save();
                    }

    				$products[] 	= [
    						'name'		=> $name,
    						'price'		=> $price,
    						'images'	=> $images
    					];
    			}

                $next_page      = $crawler->filter('.pagination_next.disabled');
                if(sizeof($next_page) < 1) break;

    		}
        }
    }


    public function getAmble()
    {
        set_time_limit(10000);
        $base_url   = 'http://amblefootwear.com/';
        $client 	= new Client();
		$products 	= [];
        $template_replaced 	  = '{{page}}';


        $template_urls = [
            $base_url . 'Products.aspx?id=5',
            $base_url . 'Products.aspx?id=19',
            $base_url . 'Products.aspx?id=6',
            $base_url . 'Products.aspx?id=11',
            $base_url . 'Products.aspx?id=20',
            $base_url . 'Products.aspx?id=12'
        ];

        foreach ($template_urls as $key => $template_url)
        {
    		for( $i=1 ; $i<=5 ; $i++ )
    		{
    			$url 		= str_replace($template_replaced,$i,$template_url);

                $header_url	= get_headers($url, 1);
    			if($header_url[0] == 'HTTP/1.0 404 Not Found') break;

    			$crawler 	= $client->request('GET' , $url);

    			$products_crawler 	= $crawler->filter('a.hover-any');

    			foreach ($products_crawler as $key => $product_crawler)
    			{
    				$node 	= new Crawler($product_crawler);
    				$url_page_link	= $node->attr('href');
    				$page_crawler	= $client->request('GET', $url_page_link);

    				$name 			= $page_crawler->filter('#ContentPlaceHolderBody_ContentPlaceHolderBreadCrumb_lblNamaProduk')->text();
    				$price 			= $page_crawler->filter('#lblHargaAwal')->text();
                    $sale_price     = $page_crawler->filter('#lblHarga')->text();
                    $desc           = $page_crawler->filter('#cssmenu ul li div')->text();

    				$images 		= [];
    				$images_obj 	= $page_crawler->filter('.slider-relative img');

    				foreach($images_obj as $image_obj)
    				{
    					$images[] 	= $image_obj->getAttribute('src');
    				}

    				// header(s'Content-Type: application/json');

                    $product                = new Product;
                    $product->title         = $name;
                    $product->price         = filter_var($price, FILTER_SANITIZE_NUMBER_INT);
                    $product->description   = preg_replace('/\s+/', ' ',$desc);
                    $product->sale_price    = filter_var($sale_price, FILTER_SANITIZE_NUMBER_INT);
                    $product->url           = $url_page_link;
                    $product->save();

                    foreach ($images as $key => $image)
                    {
                        $photo                  = new ProductPhoto;
                        $photo->photo_url       = str_replace('./',$base_url,$image);
                        $photo->thumbnail_url   = str_replace('./',$base_url,$image);
                        $photo->product_id      = $product->id;
                        $photo->save();
                    }

    				$products[] 	= [
    						'name'		=> $name,
    						'price'		=> $price,
    						'images'	=> $images
    					];
    			}

                $next_page      = $crawler->filter('.pagination_next.disabled');
                if(sizeof($next_page) < 1) break;

    		}
        }

    }

    public function getKivee()
    {
        set_time_limit(10000);
        $base_url   = 'http://www.kiveeshop.com/';
        $client 	= new Client();
		$products 	= [];
        $template_replaced 	  = '{{page}}';


        $template_urls = [
            $base_url . '4-tops',
            $base_url . '14-outwears',
            $base_url . '15-dresses',
            $base_url . '13-bottoms'
        ];

        foreach ($template_urls as $key => $template_url)
        {
    		for( $i=1 ; $i<=5 ; $i++ )
    		{
    			$url 		= str_replace($template_replaced,$i,$template_url);

                $header_url	= get_headers($url, 1);
    			if($header_url[0] == 'HTTP/1.0 404 Not Found') break;

    			$crawler 	= $client->request('GET' , $url);

    			$products_crawler 	= $crawler->filter('.ajax_block_product a');

    			foreach ($products_crawler as $key => $product_crawler)
    			{
    				$node 	= new Crawler($product_crawler);
    				$url_page_link	= $node->attr('href');
    				$page_crawler	= $client->request('GET', $url_page_link);

    				$name 			= $page_crawler->filter('#ContentPlaceHolderBody_ContentPlaceHolderBreadCrumb_lblNamaProduk')->text();
    				$price 			= $page_crawler->filter('#lblHargaAwal')->text();
                    $sale_price     = $page_crawler->filter('#lblHarga')->text();
                    $desc           = $page_crawler->filter('#cssmenu ul li div')->text();

    				$images 		= [];
    				$images_obj 	= $page_crawler->filter('.slider-relative img');

    				foreach($images_obj as $image_obj)
    				{
    					$images[] 	= $image_obj->getAttribute('src');
    				}

    				// header(s'Content-Type: application/json');

                    $product                = new Product;
                    $product->title         = $name;
                    $product->price         = filter_var($price, FILTER_SANITIZE_NUMBER_INT);
                    $product->description   = preg_replace('/\s+/', ' ',$desc);
                    $product->sale_price    = filter_var($sale_price, FILTER_SANITIZE_NUMBER_INT);
                    $product->url           = $url_page_link;
                    $product->save();

                    foreach ($images as $key => $image)
                    {
                        $photo                  = new ProductPhoto;
                        $photo->photo_url       = str_replace('./',$base_url,$image);
                        $photo->thumbnail_url   = str_replace('./',$base_url,$image);
                        $photo->product_id      = $product->id;
                        $photo->save();
                    }

    				$products[] 	= [
    						'name'		=> $name,
    						'price'		=> $price,
    						'images'	=> $images
    					];
    			}

                $next_page      = $crawler->filter('.pagination_next.disabled');
                if(sizeof($next_page) < 1) break;

    		}
        }

    }


    public function getPhoto()
    {
        $photos     = ProductPhoto::where('id','>','1482')->get();

        foreach ($photos as $key => $value) {
            echo "<img src='$value->photo_url' width='360px'>";
        }
    }
}
