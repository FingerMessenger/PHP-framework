<?php

namespace App\Module\Test\Controller;

use App\Framework\Controller;
use App\Framework\Config;
use App\Framework\Pool;

use Tester\Assert;

class TestController extends Controller
{
    public function Common()
    {
        $std = new \StdClass();
        $std->prop = "abc";
        Pool::set("std", $std);
        $stdRes = Pool::get("std");
        Assert::false($stdRes === null);
        if ($stdRes) {
            Assert::same("abc", $std->prop);
        }
        
        Assert::true(isset($this->request));
        $request = Pool::get("app_request");
        Assert::false($request === null);
        if ($request) {
            //url is test/test/common
            Assert::same("test", $request->getUri(0));
            Assert::same("test", $request->getUri(1));
            Assert::same("common", $request->getUri(2));
            Assert::same("abc", $request->getUri(3, "abc"));
            Assert::false($request->isAjax());
            Assert::true(in_array($request->getProtocol(), array("http", "https")));
            
            Assert::true(isset($this->response));
            $response = Pool::get("app_response");
            Assert::false($response === null);
            if ($response) {
                Assert::noError(function() use($response) {
                    $response->setHeaders(array(
                        "Content-type:text/html;charset=utf-8"
                    ));
                    $response->setBody("content");
                });

                Assert::true(in_array("Content-type:text/html;charset=utf-8", $response->getHeaders()));
                Assert::same("content", $response->getBody());

                $this->view(array("name" => "content"), false);
                Assert::same("headercontentfooter", $this->response->getBody());

                $this->loadModels(array("Test"));
                Assert::true(isset($this->TestModel));
                Assert::true($this->TestModel instanceof \App\Framework\Model);

                $config = $this->loadConfig("Test");
                Assert::true($config instanceof \App\Framework\Config);
                Assert::same("abc", $config->get("token"));
            }
        }
    }

    public function Web()
    {

    }

    public function Cli()
    {
        Assert::true($this->request->isCli());

    }
}