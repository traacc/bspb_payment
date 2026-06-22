<?php

namespace BspbSDK\Other;

/*
 * Описание класса на русском языке.
 *
 * Цель этого класса - провести корректировку имеющейся корзины заказа таким образом, чтобы сопоставить сумму имеющейся корзины товаров с суммой заказа, полученной от CMS.
 * Потребности в этом классе - ошибки расчетов стоимости заказа в системах WP, Joomla, Drupal, некоторых других.
 * Ошибки расчета стоимости заказа в CMS происходят из-за конвертации валют, применения ставок НДС, применения скидок и т. д, и т.п. в результате чего сумма позиций и скидки не сходится с расчетной стоимостью заказа CMS.
 *
 * В конструктор необходимо передать подготовленный массив корзины заказа с содержимым товаров и оплаченной доставки.
 * Обязательные поля каждого элемента такого массива:
 *      qty (float) - кол-во товара,
 *      price (float) стоимость 1 ед товара,
 *      name (string) - наименование товара,
 *      type (значение - 'dlv') - только для обозначения платной доставки.
 * Остальные поля массива в произвольном виде.
 *
 * Для успешной работы требуется исключать корзины товаров, где имеются лишь товары с кол-вом менее 1 ед. и без платной доставки.
 * Обязательное требование - наличие в корзине хотя бы одного товара с кол-вом равным или более 1 ед. ИЛИ наличием платной доставки.
 *
 * Использование. В конструктор передать подготовленный массив корзины товаров и расчетную CMS стоимость заказа, в соответствии, с которой, будет производиться корректировка корзины товаров.
 *
 * Базовое использование - вызвать  correctBasket(). В ответе будет массив с откорректированной корзиной товаров.
 * Проверить нужно ли корректировать корзину заказов - checkNoNeedRecalc() - вернет true если ничего корректировать не нужно.
 *
 * Схемы работы.
 * 1 - если в корзине есть платная доставка, разница между расчетной и полученной стоимостью заказа сбрасывается в стоимость платной доставки.
 * 2 - если в корзине есть единичный товар, разница ... сбрасывается в единичный товар.
 * 3 - если в корзине нет ни единичного товара ни платной доставки - выбирается позиция товара где кол-во товаров более 1, из позиции отделяется 1 товар, создается новая товарная позиция с новым единичным товаром, разница сбрасывается в новый единичный товар.
 *
 * --------------------------------------------------------------------
 *
 * Дополнительные возможности - пересчет корзины товаров с учетом скидки или наценки.
 * Если мы знаем что применяется скидка или наценка на стоимость заказа, которую CMS не пересчитывает, мы можем указать совершить пересчет стоимости каждого товара в соответствии с указанной скидкой.
 * Примечание: скидка "размазывается" по всей корзине товаров и не подлежит последующей отмене, поскольку это не предусмотрено API платежной системы.
 *
 * recalcWODelivery() - применяется пересчет корзины товаров БЕЗ пересчета службы доставки (даже если в заказе есть платная служба доставки). Стоимость службы доставки после пересчета остается без изменения. После пересчета автоматически применяется корректировка корзины (если есть платная доставка, то ее стоимость может немного измениться из-за корректировки).
 * recalcWithDelivery() - применяется пересчет всей корзины товаров вместе с доставкой. После пересчета автоматически применяется корректировка. Наличие в корзине службы доставки обязательно!!!
 *
 */

class CalcBasketFixer {

	private $basketData;
	private $orderPrice;
	private $oneProductKey;
	private $shipmentKey;

	/**
	 * @param array $basketData
	 * required item keys: qty (float), price (float), name (string), type ('dlv' - only for shipment)
	 * required item key for delivery item: $item['type'] = 'dlv'.
	 */
	public function __construct(array $basketData, float $orderPrice) {
		$this->orderPrice = $orderPrice;
		foreach ($basketData as &$item) {
			$item['price'] = floatval($item['price']);
			$item['qty'] = floatval($item['qty']);
		}
		unset($item);
		$this->basketData = $basketData;

		foreach ($this->basketData as $key => $item) {
			if (
				isset($item['type'])
				&& ($item['type']=='dlv')
				&& ($item['price'] > 0)
			) {
				$isDelivery = true;
				$this->shipmentKey = $key;
			}

			if (
				($item['qty'] == 1)
				&& ( empty($item['type']) || ($item['type'] != 'dlv') )
			) {
				$isOneProduct = true;
				$this->oneProductKey = $key;
			}
		}

	}

	public function checkNoNeedRecalc()
	{
		$amount = 0;
		foreach ($this->basketData as $item) {
			$amount += round( $item['price'] * $item['qty'], 2);
		}
		return $amount == $this->orderPrice;
	}

	public function correctBasket()
	{
		$isDelivery = ($this->shipmentKey !== null);
		$isOneProduct = ($this->oneProductKey !== null);

		if ($this->checkNoNeedRecalc()) return $this->basketData;

		if (!$isDelivery) {
			if (!$isOneProduct) $this->scenario1();
			else $this->scenario2();
		} else $this->scenario3();

		return $this->basketData;
	}

	//Пересчет стоимости каждого товара пропорционально примененной скидке.
	//Пересчет ведется без доставки - стоимость доставки не пересчитывается.
	public function recalcWODelivery()
	{
		$deliveryPrice = 0;
		if ($this->shipmentKey !== null) {
			$deliveryPrice = $this->basketData[$this->shipmentKey]['price'];
		}

		$baseOrderAmount = $this->orderPrice - $deliveryPrice;
		$currentOrderAmount = 0;
		foreach ($this->basketData as $key => $item) {
			if ($key === $this->shipmentKey) continue;
			$currentOrderAmount += round($item['price'] * $item['qty'], 2);
		}
		$kf = $currentOrderAmount / $baseOrderAmount;

		foreach ($this->basketData as $key => &$bItem) {
			if ($key === $this->shipmentKey) continue;
			$bItem['price'] = round($bItem['price'] / $kf, 2);
		}
		unset($bItem);
		//correct
		return $this->correctBasket();
	}

	//пересчет ведется, в том числе, и у доставки, стоимость доставки меняется.
	public function recalcWithDelivery()
	{
		if ($this->shipmentKey === null) return null; //Error - delivery not found!

		$currentOrderAmount = 0;
		foreach ($this->basketData as $key => $item) {
			$currentOrderAmount += round($item['price'] * $item['qty'], 2);
		}
		$kf = $currentOrderAmount / $this->orderPrice;
		foreach ($this->basketData as $key => &$bItem) {
			$bItem['price'] = round($bItem['price'] / $kf, 2);
		}
		unset($bItem);
		//correct
		return $this->correctBasket();
	}

	private function scenario1()
	{
		$compItem = [];
		foreach ($this->basketData as &$bItem) {
			if ($bItem['qty'] > 1) {
				$compItem = $bItem;
				$compItem['qty'] = 1;
				$compItem['name'].=' - 1 ед';
				$compItem['is_new'] = 'yes';
				$bItem['qty'] -= 1;
				break;
			}
		}
		unset($bItem);
		if (empty($compItem)) return false; //ERROR!!! Cant recalc basket!!!

		$orderAmount = 0;
		foreach ($this->basketData as $bItem) {
			$orderAmount += round($bItem['price'] * $bItem['qty'],2);
		}
		$compItem['price'] = $this->orderPrice - $orderAmount;
		if ($compItem['price'] < 0) $compItem['price'] = $compItem['price'] * -1;
		$this->basketData[] = $compItem;
	}

	private function scenario2()
	{
		$orderAmount = 0;
		foreach ($this->basketData as $key => $bItem) {
			if ($key != $this->oneProductKey) $orderAmount += round($bItem['price'] * $bItem['qty'],2);
		}
		$this->basketData[$this->oneProductKey]['price'] = $this->orderPrice - $orderAmount;
		if ($this->basketData[$this->oneProductKey]['price'] < 0) $this->basketData[$this->oneProductKey]['price'] = $this->basketData[$this->oneProductKey]['price'] * -1;
	}

	private function scenario3()
	{
		$orderAmount = 0;
		foreach ($this->basketData as $key => $bItem) {
			if ($key != $this->shipmentKey) $orderAmount += round($bItem['price'] * $bItem['qty'],2);
		}
		$this->basketData[$this->shipmentKey]['price'] = $this->orderPrice - $orderAmount;
		if ($this->basketData[$this->shipmentKey]['price'] < 0) $this->basketData[$this->shipmentKey]['price'] = $this->basketData[$this->shipmentKey]['price'] * -1;
	}

}
