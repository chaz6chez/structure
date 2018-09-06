<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
require __DIR__ . '/../vendor/autoload.php';
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
}

$a = new Go();
print_r($a->to());
