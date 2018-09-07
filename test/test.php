<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
require __DIR__ . '/../vendor/autoload.php';
function dump($var){
    print_r($var);
    exit;
}
header("Content-type: text/html; charset=utf-8");
class Go{
    /**
     * @return array|null|string
     * @throws ReflectionException
     */
    public function to(){
        $a = \Test\Check::factory([
            'a' => '12344',
            'b' => '1',
        ]);
        $a->validate();
        return $a;
    }
    public function test(){
        $e = new \InvalidArgumentException('Invalid Filter Specified: ',2);
        return $e->getMessage();
    }
}

$a = new Go();
//print_r($a->test());
print_r($a->to());
