<?php
//$fileTowns = fopen("towns.txt", 'r');
$colors = [];
$items = [];
$towns = [];

$fileCoefs = fopen("colorCoef.txt", 'r');
while(!feof($fileCoefs)){
    $strArrCoef = explode(" ", fgets($fileCoefs));
    $colors += [ hash( 'sha256', $strArrCoef[0]) => [ 'name' => $strArrCoef[0], 'coef' => $strArrCoef[1]] ];
}
fclose($fileCoefs);

$fileItems = fopen("price.txt", 'r');
while(!feof($fileItems)){
    $strArrItems = explode(" ", fgets($fileItems));
    if($strArrItems[0] == 'Актуальные'){ continue; };
    $items += [ hash( 'sha256', $strArrItems[0]) => [ 'name' => $strArrItems[0], 'cost' => $strArrItems[1]] ];
}
fclose($fileItems);
$fileT = fopen("towns.txt", 'r');
while(!feof($fileT)){
    $strArrCoef = fgets($fileT);
    $towns += [ hash( 'sha256', $strArrCoef) =>  $strArrCoef ];
}
fclose($fileT);

if ((!empty($_POST)) && $_POST["offerBtn"] == "offer"){
    /*
    <summary>
    коэфф - coef
            список объектов по ключам: arrObject
            фамилия: name
            город: town
            Дата: date
            Адрес: address
            итоговая цена: resultCost
            кол-во объектов: factObjectAmounts
            рандомный номер документа randomNumber
    </summary>
    */
    $coef = 1;
    $fullCost = 0;
    require_once 'vendor/autoload.php';

    if (!empty($_FILES["fileChoice"]['tmp_name']))
    {
        $filePrices = fopen($_FILES["fileChoice"]['tmp_name'], 'r');
        $items = [];
        while(!feof($filePrices)){
            $strArrItems = explode(" ", fgets($filePrices));
            if($strArrItems[0] == 'Актуальные'){ continue; };
            $items += [ hash( 'sha256', $strArrItems[0]) => [ 'name' => $strArrItems[0], 'cost' => $strArrItems[1]] ];
        }
        fclose($filePrices);
    }

    $arrObject = [];
    if(!empty($_POST)) {
        $name = $_POST["name"];
        $town = $_POST["town"];
        $date = $_POST["date"];
        $address = $_POST["address"];
        $colorChosen = $_POST["color"];
        $coef = $colors[$colorChosen]['coef'];
        $amountObject = count($items);
        $amountColors = count($colors);

        foreach ($items as $i => $v){
            if (!key_exists($i, $_POST) ){
                continue;
            }
            $arrObject +=
                [
                    $i =>
                        [
                            "name" => $v['name'],
                            "cost" => trim($v['cost']),
                            "amount" => ((!empty($_POST["amount" . $i]))? trim($_POST["amount" . $i]) : 0)
                        ]
                ];
            $fullCost += (int)trim($v['cost']) * (int)trim($_POST["amount" . $i]);
        }
//alignment
        $randomNumber = rand(1000, 9999);
        $resultCost = $fullCost * $coef;
        $imgShtrix = __DIR__ . "/штрих.JPG";
        \PhpOffice\PhpWord\Settings::setPdfRendererName(\PhpOffice\PhpWord\Settings::PDF_RENDERER_DOMPDF);
        \PhpOffice\PhpWord\Settings::setPdfRendererPath('../../' . __DIR__);
        header("Content-Description: File Transfer");
        header('Content-Disposition: attachment; filename="' . 'Документ на выдачу №'. $randomNumber . '.pdf');
        header('Content-Type: application/pdf');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('dejavu sans');
        $phpWord->setDefaultFontSize(12);

        $shtrix = $phpWord ->addSection();
        $shtrix ->addImage($imgShtrix, array( 'width' => 250, 'marginLeft' => 20 ));

        $title_section = $phpWord ->addSection();
        $title_section ->addText('Накладная № '.$randomNumber, array( 'size' => 16, 'bold' => true ), array( 'align' => 'center' ));

        $info_section = $phpWord -> addSection();
        $info_section ->addText('Адрес получения заказа: г. '. $towns[$town] . ', ' . $address, array( 'size' => 12 ), array( 'align' => 'center' ));

        $info_section ->addText('Дата получения заказа: '.$date, array( 'size' => 12 ), array( 'align' => 'center' ));

        $table_section = $phpWord ->addSection();
        $table = $table_section ->addTable();

        $table -> addRow() ;
        $cell_1_1 = $table -> addCell(200);
        $cell_1_1 -> addText(' ');
        foreach($arrObject as $i => $v) { $cell_1_1 -> addListItem($v['name'] .', '. $v['amount'] .' шт - ' . $v['cost']  . 'р'); }
        $cell_1_1 -> addText(' ');

        $cell_1_2 = $table -> addCell(200);
        $cell_1_2 -> addText('Сумма: '. $fullCost);

        $table -> addRow();
        $cell_2_1 = $table -> addCell(200);

        $cell_2_1 -> addText('Цвет '. $colors[$colorChosen]['name'] . ', наценка ' . $coef);

        $table -> addRow();
        $cell_3_1 = $table -> addCell(200);
        $cell_3_1 -> addText('Итого:', array( 'bold' => true));
        $cell_3_2 =$table -> addCell(200);
        $cell_3_2 -> addText($resultCost, array( 'bold' => true));

        $fin_section = $phpWord ->addSection();
        $text = $fin_section -> addTextRun();
        $text -> addText('Всего наименований ' . count($arrObject).', на сумму ');
        $text -> addText($resultCost.',00 руб.', array( 'bold' => true));

        $guarantee_section = $phpWord -> addSection();
        $guarantee = fopen(__DIR__ . "/garanty.txt",'r');
        $guarantee_arr=[];
        while(!feof($guarantee)) { $guarantee_arr[] .= fgets($guarantee); }
        fclose($guarantee);
        $guarantee_section -> addText( $guarantee_arr[0], array( 'bold' => true ), array( 'align' => 'center' ));
        for($i = 1; $i < count($guarantee_arr); $i++)
        {
            $guarantee_section -> addListItem($guarantee_arr[$i], 0, null,
                array('listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_ALPHANUM ), array( 'align' => 'center' ));
        }

        $pdf = new PhpOffice\PhpWord\Writer\PDF\DomPDF($phpWord);
        $pdf->save('php://output');
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
</head>
<body>
    <main>
        <h1>Заказ мебели</h1>
        <form action="index.php" method="post" enctype="multipart/form-data">
            <div style="display: grid; width: 20vw; gap: 1vh;">
                <div style="display: flex; justify-content: end"><label style="margin-right: auto">Фамилия</label><input type="text" name="name" id="name" placeholder="Фамилия" style="margin-left: auto; width: 10vw"></div>
                <div style="display: flex; justify-content: end"><label style="margin-right: auto">Город доставки</label>
                    <select name="town" style="margin-left: auto; width: 10vw">
                        <?php
                        foreach($towns as $i => $v) { echo '<option  value="'. $i . '">' . $v . '</option>'; }
                        ?>
                    </select></div>
                <div style="display: flex; justify-content: end"><label style="margin-right: auto">Дата</label><input type="date" name="date" id="date" placeholder="date" style="margin-left: auto; width: 10vw"></div>
                <div style="display: flex; justify-content: end"><label style="margin-right: auto">Адрес</label><input type="text" name="address" id="address" placeholder="Адрес" style="margin-left: auto; width: 10vw"></div>
            </div>
            <div style="display: flex; gap: 2vw; margin-bottom: 2vh">
            <div>
                <p>Выбор цвета: </p>
                <?php
                foreach ($colors as $i => $v0 ){ echo '<div> <input type="radio" name="color" id="' . $i . '" value="' . $i . '"><label>' . $v0['name'] . '</label> </div>'; } ?>
            </div>
            <div>
                <p>Предмет мебели: </p>
                <?php foreach ($items as $i => $v) { echo '<div> <input type="checkbox" name="' . $i .'" value="' . $i . '"/> <label>' . $v['name'] . '</label> </div>'; } ?>
            </div>
            <div style="display: grid; width: 20%;">
                <p>Количество: </p>
                <?php foreach ($items as $i => $v) { echo '<div style="display: flex;"> <input type="number" name="amount' . $i . '" id="amountBanketka"> <label>' . $v['name'] . '</label> </div>'; } ?>
            </div>
            </div>
            <div style="display: grid; width: 15vw; gap: 1vh">
                <input type="file" name="fileChoice" accept=".txt"/>
                <button type="submit" name="offerBtn" value="offer">Оформить заказ</button>
                <button type="submit" name="offer" value="fake">fake submit</button>
            </div>
        </form>
    </main>
</body>
</html>