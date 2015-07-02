# CodeigniterCardomPayment
Easy Implement Of Cardcom Payment Api (lowprofile) (Cardcom : http://www.cardcom.co.il/)
This Implement The lowprofile Iframe Or Redrice type of payment (http://kb.cardcom.co.il/article/AA-00412/61/)
You can feel free to extend this and support few more types of payments.

Install
  Copy Application File To your Codeigniter Framework Folder
  
  Loading Libary
    		$this->load->library('cardcom_payment');
  Initial Config Params:
    enter to application/config/cardcom_payment , and change the config parameters.
    terminal_number - number of your private terminal (1000 for testing)
    username - your username if cardcom system
    api_level - currectly support fully api 9
    codepage
  
