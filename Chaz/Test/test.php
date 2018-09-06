<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
require __DIR__ . '/../../vendor/autoload.php';
class Go{
    public function to(){
        $a = \Chaz\Test\Check::factory(['check' => 12345]);
        $a->validate();
        if($a->hasError()){
            return $a->getError();
        }
        return $a;
    }
}

$a = new Go();
print_r( $a->to());
