<?
require_once(__DIR__ . '/crest.php');

function debug($var)
{
    echo '<pre>';
    print_r($var);
    echo '<pre>';
}


$sound = false;

function get_deals()
{
    // получаем сделки
    $deals_data = CRest::call('crm.deal.list', array('filter' => array('STAGE_ID' => 'PREPARATION'), 'select' => array("UF_*", "*"),));

    if (!empty($deals_data['result'])) {
        $result = array();
        foreach ($deals_data['result'] as $k => & $deal) {
            $result[$k]['ID'] = $deal['ID'];

            // товарные позиции сделки
            $shop_data = CRest::call('crm.deal.productrows.get', array('id' => $deal['ID'],));

            if (!empty($shop_data['result'])) {
                $result[$k]['ZAKAZ'] = array();
                foreach ($shop_data['result'] as $i => & $shop) {
                    $result[$k]['ZAKAZ'][$i]['QUANTITY'] = $shop['QUANTITY'];
                    $result[$k]['ZAKAZ'][$i]['NAME'] = $shop['PRODUCT_NAME'];

                    // срок приготовления заказа
                    date_default_timezone_set('Asia/Yekaterinburg');
                    $result[$k]['ZAKAZ'][$i]['SROK_PRIGOTOVLENIA'] = date('Y-m-d H:i:s', strtotime($deals_data['result'][$k]['UF_CRM_1640073006565']));

                    // если статус заказа новый
                    if ($deals_data['result'][$k]['UF_CRM_1640842110182']) {

                        // удаляем статус новый
                        CRest::call('crm.deal.update', ['id' => $deal["ID"], 'fields' => ["UF_CRM_1640842110182" => '0']]);
                        $result[$k]['ZAKAZ'][$i]['MARKER'] = 'marker';

                        global $sound;
                        //ОТКЛЮЧИЛ ЗВУК
                        //$sound = true;
                    }
                }
            }
        }
    }
    return $result;
}

$products = get_deals();
$t_header = '
<tr>
    <th>Номер заказа</th>
    <th>Состав заказа</th>
    <th>Количество</th>
    <th>Срок приготовления</th>
</tr>
';
$bool = false;
if (!empty(($products))) {
    foreach ($products as $key => $product) {
        $bool == false ? $color = '#fff' : $color = '#ffdab96e';
        if (!empty(($product["ZAKAZ"]))) {
            foreach ($product["ZAKAZ"] as $product_item) {
                $today = date("Y-m-d H:i:s"); // дата сегодня
                $product_item['SROK_PRIGOTOVLENIA']; // дата когда заказ должен быть готов
                $todayDateUnix = strtotime($today);
                $srokPrigotovleniaDateUnix = strtotime($product_item['SROK_PRIGOTOVLENIA']);
                $result = ($srokPrigotovleniaDateUnix - $todayDateUnix);
                if ($result <= 0) {
                    CRest::call('crm.deal.update', ['id' => $product["ID"], 'fields' => ["STAGE_ID" => 'PREPAYMENT_INVOICE']]);
                }
                $tr .= '
            <tr style="background:' . $color . '">
                <td>' . $product["ID"] . '</td>
                <td class="' . $product_item["MARKER"] . '">' . $product_item["NAME"] . '</td>
                <td>' . $product_item["QUANTITY"] . '</td>
                <td>' . substr($product_item['SROK_PRIGOTOVLENIA'], 11, 5) . '</td>
            </tr>
            ';
            }
        }
        $bool == false ? $bool = true : $bool = false;
    }
}

$result = array(
    "sound" => $sound,
    "table" => $t_header . $tr
);

$array = json_encode($result);
print_r($array);


