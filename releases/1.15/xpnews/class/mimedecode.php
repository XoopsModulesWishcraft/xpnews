<?php


	class mime_decode
	{
		var $html;
		var $text;
		var $attachments = array();
		var $headers = array();
		var $body = array();
		var $inline_images = array();
			
		function __construct($message)
		{
			@$this->pullapart(($message));
		}
		
		private function pullapart($message)
		{
			$this->headers = $message['header'];
			$this->body = $message['body'];
			$body = array();
			if (strpos(strtolower($this->body[0]), 'mime format'))
			{
				foreach($this->body as $key => $line)
				{
					if ($key>0)
					{
						if ($key==1||$key==2)
							if (!empty($line))
							{
								$boundary = explode("_", $line);
								$lastpart = $boundary[count($boundary)-1];
							}
					
						if (!empty($lastpart)) {
							if (strpos(' '.$line, $lastpart)>0){
								$inheader=true;
								$part++;	
							}
						} elseif (strpos(' '.$line, 'Content-Type')>0){
							$inheader=true;
							$part++;	
						}
						if ($inheader==true&&empty($line))
							$inheader=false;
							
						if ($inheader==true) {
							if (strpos($line, ":")>0)
							{
								$i = strpos($line, ":");
								$item = trim(substr($line, 0, $i));
								$setting = str_replace(array('"',';'),'',trim(substr($line, $i+1, strlen($line)-$i-1)));
								$body[$part]['header'][$item]['item'] = $setting;
							} elseif (strpos($line, "=")>0) {
								$i = strpos($line, "=");
								$subitem = trim(substr($line, 0, $i));
								$setting = str_replace(array('"',';'),'',trim(substr($line, $i+1, strlen($line)-$i-1)));
								$body[$part]['header'][$item][$subitem] = $setting;
							}
						} else {						
							if (!empty($lastpart)) {
								if (strpos(' '.$line, $lastpart)==0){
									$body[$part]['body'] .= $line."\n";
								}
							} else {
								$body[$part]['body'] .= $line."\n";
							}
						}
						
					}
				}			
				
				$this->processBodyArray($body);
			} else {
				$this->text = implode("\n", $this->body);
			}

		}

		private function processBodyArray($body)
		{
			global $xoopsModuleConfig;
			
			foreach($body as $key => $part)
			{
				if ($part['header']['Content-Disposition']['item']!='attachment') {
					switch($part['header']['Content-Type']['item']) {
					case "multipart/related":
					case "multipart/alternative":
						break;
					case "text/plain":
						$this->text = $part['body'];
						break;
					case "text/html":
						$this->html = $part['body'];
						break;
					case "image/jpeg":
					case "image/png":					 
					case "image/gif":
					case "image/tiff":					
						if ($part['header']['Content-Transfer-Encoding']['item'] == 'base64') {
							$filename = $_GET['category'].'_'.$_GET['group'].$_GET['artnum'].'_'.$part['header']['Content-Type']['name'];
							if (!file_exists($xoopsModuleConfig['decode_path'].'/'.$filename))
							{
								$file = fopen($xoopsModuleConfig['decode_path'].'/'.$filename, "w");
								fwrite($file, base64_decode($part['body']), strlen(base64_decode($part['body'])));
								fclose($file);
							}								
							$inline++;
							$this->inline_images[$inline]['filename'] = $filename;
							$this->inline_images[$inline]['name'] = $part['header']['Content-Type']['name'];											
							$this->inline_images[$inline]['url'] = XOOPS_URL.$xoopsModuleConfig['decode_path_access'].'/'.$filename;
						}
							
					}
				} else {			
					if ($part['header']['Content-Transfer-Encoding']['item'] == 'base64') {
						if (strlen($part['header']['Content-Type']['name'])>0)
							$filename = $_GET['category'].'_'.$_GET['group'].$_GET['artnum'].'_'.$part['header']['Content-Type']['name'];
						else
							$filename = $_GET['category'].'_'.$_GET['group'].$_GET['artnum'].'_'.$part['header']['Content-Disposition']['filename'];

						if (!file_exists($xoopsModuleConfig['decode_path'].'/'.$filename)){
							$file = fopen($xoopsModuleConfig['decode_path'].'/'.$filename, "w");
							fwrite($file, base64_decode($part['body']), strlen(base64_decode($part['body'])));
							fclose($file);
						}
												
						$attachment++;
						$this->attachments[$attachment]['filename'] = $filename;
						
						if (strlen($part['header']['Content-Type']['name'])>0)
							$this->attachments[$attachment]['name'] = $part['header']['Content-Type']['name'];
						else
							$this->attachments[$attachment]['name'] = $part['header']['Content-Disposition']['filename'];
						
						$this->attachments[$attachment]['url'] = XOOPS_URL."/modules/xpnews/download.php?category=".$_GET['category'].'&group='.$_GET['group']."&artnum=".$_GET['artnum'].'&filename='.$part['header']['Content-Disposition']['filename']."";
					}
				}
			}
			
			$this->processInlineImages();
		}

		private function processInlineImages()
		{
			$i=1;
			while($i >0)
			{
				$i = strpos($this->html, '<img', $i);
				if ($i>0) {
					$ii = strpos($this->html, '>', $i);
					$img = substr($this->html, $i, $ii- $i+1);
					$i = $ii;
					foreach ($this->inline_images as $key => $image)
					{
						if (strpos($img, $image['name'])>0)
						{
							$this->html = str_replace($img, '<img src="'.$image['url'].'" alt="'.$image['name'].'">',$this->html);
						}
					}
				}			
			}
		
		}

		function getBody()
		{
			if (strlen($this->html)>0)
				return str_replace("=\n","\n",$this->html);
			else
				return str_replace("\n","<br />",htmlspecialchars($this->text));
		}

		function getTextBody()
		{
			return $this->text;
		}


		function getAttachments()
		{
			return $this->attachments;
		}
		
		function getHeader()
		{
			return $this->headers;
		}
		
		function from()
		{
			foreach($this->headers as $key => $data)
			{
				if (strpos(' '.$data, 'From:'))
				{
					return substr($data,6,strlen($data)-6);
				}
			}
		}
		
		function subject()
		{
			foreach($this->headers as $key => $data)
			{
				if (strpos(' '.$data, 'Subject:'))
				{
					return substr($data,9,strlen($data)-9);
				}
			}
		}
		
		function date()
		{
			foreach($this->headers as $key => $data)
			{
				if (strpos(' '.$data, 'Date:'))
				{
					return substr($data,6,strlen($data)-6);
				}
			}
		}
		
		function to()
		{
			foreach($this->headers as $key => $data)
			{
				if (strpos(' '.$data, 'Newsgroups:'))
				{
					return substr($data,12,strlen($data)-12);
				}
			}
		}
	}
?>