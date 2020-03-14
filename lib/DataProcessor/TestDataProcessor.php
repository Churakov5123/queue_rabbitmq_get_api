<?php

declare(strict_types=1);

namespace SuiteCRM\Custom\DataProcessor;

use BeanFactory;

class TestDataProcessor
{
    public function process(array $data): void
    {
        //0. предварительно удаляем ппрошлые пакеты  и  чистим связи
		

        $id = $data["id"];
        $data["date"];
        $data["balance"];
        $data["rating"];
        $data["spends"]["week"];
        $data["spends"]["month"];
        $data["url"];
        $data["phone"];// телефоны +
        $data["email"];// почта  +
        $data["city"];// город   +
        $data["url_stat"];//ссылка на статистику  +
        $data["company"];//название  организациии +
        $data["date_start"];//Дата начала размещения:  +
        $parser_c= 'https://test.test/admin/goods/packet/list/all?login='.$id.''; //+
        $cabinet_c='http://www.test.test/user/'.$id.'/profile/';//+
        $goods_all_c ='http://www.test.test/user/'.$id.'/?type=good';//+

        
        // 1. добавление в таблицу accounts  основной информации - потом можно будет дополнить ...
        $sql = "UPDATE accounts set balance ='" . $data["balance"] . "', goods_all_c='" .$goods_all_c. "', cabinet_c='" . $cabinet_c. "', parser_c='" . $parser_c . "', test_statistic='" . $data["url_stat"] . "', data_parser_created_c='" . $data["date_start"] . "', billing_address_city='" . $data["city"] . "', phone_office='" . $data["phone"] . "', accounts.name='" . $data["company"] . "', rating='" . $data["rating"] . "', date_relevance='" . $data["date"] . "', spends_week='" . $data["spends"]["week"] . "', spends_month='" . $data["spends"]["month"] . "', website='" . $data["url"] . "' where  test_id= $id";
        $GLOBALS['db']->query($sql);
        //  достаем id email  привязанного к аккаунту
        $sql = "select email_addresses.id  as id from accounts
                  left join email_addr_bean_rel  on accounts.id=email_addr_bean_rel.bean_id
                  INNER JOIN email_addresses on email_addresses.id=email_addr_bean_rel.email_address_id
                  where  test_id=$id and  email_addresses.deleted = 0";
        $result = $GLOBALS['db']->query($sql, true);
        $row = $GLOBALS['db']->fetchByAssoc($result);
        $mailId = $row['id']; //id   email

        // обновляем  email в таблице email_addresses при наличии существубщих связей, через been не получилось реализовать выдает ошибку связи
        $qwe = "UPDATE  email_addresses  SET email_address='".$data["email"]."' where id= '$mailId'";
        $GLOBALS['db']->query($qwe);
        //2. получаем бин аккаунта ( мне нужно получить бин по  test_id) тут делаем по доке  https://docs.suitecrm.com/developer/working-with-beans/
        //http://www.howtosolvenow.com/sugar-beanfactory/

        $sql = "SELECT id  FROM  accounts where  test_id= $id and deleted = 0 ";
        $result = $GLOBALS['db']->query($sql, true);
        $row = $GLOBALS['db']->fetchByAssoc($result);
        $beanId = $row['id']; //id been

        //3  идем по ассоциативному масиву пакетов
        foreach ($data['packets'] as $packet) {
            $packet_id = $packet["id"]; //  id   пакета
            //  получаем  бин компании по id  для дальнейшего добавления   в таблицу  связей
            $accountBean = BeanFactory::getBean('Accounts', $beanId);
            // Load the relationship
            $accountBean->load_relationship('accounts_api_packets_1');
            // Create a new demo api_packets ! ! !
            $api_packetsBean = BeanFactory::newBean('Api_Packets');
            $api_packetsBean->name = $packet_id;
            $api_packetsBean->packets_id = $packet_id;
            $api_packetsBean->last_import_date = $packet["last_import_date"];
            $api_packetsBean->goods_counts_present = $packet["goods_counts"]["present"];
            $api_packetsBean->goods_counts_to_order = $packet["goods_counts"]["to_order"];
            $api_packetsBean->packets_state = $packet["state"];
            $api_packetsBean->packets_city = $packet["city"];
            $api_packetsBean->ppc_views_week = $packet["ppc"]['views']['week'];
            $api_packetsBean->ppc_views_month = $packet["ppc"]['views']['month'];
            $api_packetsBean->save();
            //Link the bean to $accountBean
            $accountBean->accounts_api_packets_1->add($api_packetsBean);
            //5  получаем id paсkets системный  для сопряжаения с raits 5 ставками
            $sql = "SELECT id  FROM  api_packets  where packets_id = $packet_id  and deleted = 0 ";
            $pakets_sql = $GLOBALS['db']->query($sql, true);
            $packet_sql = $GLOBALS['db']->fetchByAssoc($pakets_sql);
            $packet_real_id = $packet_sql['id'];
            //7  идем по ассоциативному масиву rates
            foreach ($packet['ppc']['rates'] as $rate) {
                //  достаем бин packeta   по  id для дальнейшего добавления   в таблицу  связей
                $packetsBean = BeanFactory::getBean('Api_Packets', $packet_real_id);
                // Load the relationship
                $packetsBean->load_relationship('api_packets_apir_rates_1');
                // Create a new demo apir_rates ! ! !
                $apir_ratesBean = BeanFactory::newBean('Apir_Rates');
                $apir_ratesBean->name = $packet_id;
                $apir_ratesBean->date_rates = $rate['date'];
                $apir_ratesBean->rate_rates = $rate['rate'];
                $apir_ratesBean->context_rates = $rate['context'];
                $apir_ratesBean->save();
                //Link the bean to $packetsBean
                $packetsBean->api_packets_apir_rates_1->add($apir_ratesBean);
            }
        }
    }
}