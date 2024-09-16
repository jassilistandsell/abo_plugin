<?php declare(strict_types=1);

namespace Plugin\Abo_Mollie\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

class Migration20240906151122 extends Migration implements IMigration
{
    public function up()
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `KAbbo` (
        `ID` int(10) NOT NULL AUTO_INCREMENT,
        `Order_ID` int(10) NOT NULL,
        `Customer_ID` varchar(255) NOT NULL,
        `Start_date` date NOT NULL,
        `NextStart_date` date NOT NULL,
        `Frequency` varchar(255) NOT NULL,
        `Discounted_price` varchar(255) NOT NULL,
        `CustomerID_Mollie` varchar(255) NOT NULL,
        `OrderID_Mollie` varchar(255) NOT NULL,
        `Status` varchar(255) NOT NULL,
        PRIMARY KEY (`ID`)
        ) ENGINE=InnoDB  COLLATE utf8_general_ci");

        $this->execute("CREATE TABLE IF NOT EXISTS `tfrequency` (
        `kFrequency` int(11) NOT NULL AUTO_INCREMENT,
        `cFrequency` varchar(255) NOT NULL,
        `cFreq_coupon` varchar(255) NOT NULL,
        PRIMARY KEY (`kFrequency`)
        ) ENGINE=InnoDB  COLLATE utf8_general_ci");
    }

    public function down()
    {
        $this->execute("DROP TABLE IF EXISTS `KAbbo`");
        $this->execute("DROP TABLE IF EXISTS `tfrequency`");
    }

}

