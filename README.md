# CodeigniterCardomPayment
Easy Implement Of Cardcom Payment Api (lowprofile) (Cardcom : http://www.cardcom.co.il/)
This Implement The lowprofile Iframe Or Redrice type of payment (http://kb.cardcom.co.il/article/AA-00412/61/)
You can feel free to extend this and support few more types of payments.

## Install
#### 1. Copy Application File To your Codeigniter Framework Folder
#### 2. Loading Libary
```
    $this->load->library('cardcom_payment');
```
#### 3. Initialize Config Params:
enter to application/config/cardcom_payment , and change the config parameters.
* terminal_number - number of your private terminal (1000 for testing)
* username - your username if cardcom system
* api_level - currectly support fully api 9
* codepage
```
$config['terminal_number'] = '';
$config['username'] = '';
$config['api_level'] = 9;
$config['codepage'] = 65001;
```

## Usage
#### Get lowprofile redirect | iframe Url
```
        $cardcom = array(
			'Language'=>'he',
			'CoinID'=>'1',
			'SumToBill'=>PRICE,
			'ProductName'=>'כרטיסי נסיעה',
			'SuccessRedirectUrl'=> site_url('/order/order_success'),
			'ErrorRedirectUrl'=>site_url('/order/order_error'),
		);
		#if true will automatic redrict to url
		$this->cardcom_payment->getCardcomPaymentRedirectUrl($cardcom,true);
```
#### Set Invoice Data
if you want also to send an invoice to the buyer follow this.

```
		$invoices = array(
			'CustName' => $CUSTOMER_NAME,
			'SendByEmail' => "true" | pass as STRING,
			'Language' => 'he',
			'Email' => $EMAIL,
			'Items' => array(),
		);
		foreach ($order_data['data'] as $item) {
			$invoices['Items'][] = array(
				'Description' => $item->DESC,
				'Price' => $item->PRICE,
				'Quantity' => $item->quantity
			);	
		}
	$this->cardcom_payment->setInvoiceData($invoices);

```
  
