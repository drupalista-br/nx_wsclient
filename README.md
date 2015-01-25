A NortaoX.com disponibiliza um módulo para intermediar a sincronização de dados entre a aplicação ERP do Lojista
e o sistema e-Commerce da NortaoX.com.<br /><br />
Este módulo é multi-plataforma, ou seja, roda tanto em sistemas UNIX-like como Windows. Este manual irá focar
nos aspectos pertinentes ao Sistema Operacional Windows.

<div class="ready-accordion">
  <span class="ready-accordion-header"><h3><u>Usuário Lojista para Testes</u></h3></span>
  <ul>
	<li>Acesse <a href="http://loja.nortaoxsandbox.tk">loja.nortaoxsandbox.tk</a></li>
	<li>Clique em <code>Minha Conta</code> e selecione um Provedor de Autenticação</li>
	<li>Após fazer o login no Provedor de Autenticação, você deverá informar um <code>usuário</code>, <code>senha</code>
	e selecionar o Papel <code>lojista</code>. Anote o usuário e senha pois precisará desses dados no
	passo <code>Instalação e Configuração</code></li>
  </ul>

</div>
<div class="ready-accordion">
  <span class="ready-accordion-header"><h3><u>Instalação e Configuração</u></h3></span>
  <ul>
	<li><h4>PHP</h4>
	  <ul>
		<li>Instale o <a href="http://www.microsoft.com/en-au/download/details.aspx?id=30679">
			Visual C++ Redistributable for Visual Studio 2012</a>. Faça o download do arquivo:
			<ul>
			  <li><code>VSU_4\vcredist_x64.exe</code> caso o seu sistema seja de 64 bits ou</li>
			  <li><code>VSU_4\vcredist_x86.exe</code> caso o seu sistema seja de 32 bits</li>
			</ul>
		</li>
		<li><a href="http://windows.php.net/downloads/releases/php-5.5.21-nts-Win32-VC11-x86.zip">Clique aqui</a> para
			baixar o interpretador da linguagem PHP e descompacte o arquivo ZIP para a pasta <code>C:\php5</code>
		</li>
		<li>Baixe o arquivo <a href="https://github.com/drupalista-br/nx_wsclient/raw/master/php.ini">
			php.ini</a> ( clique o botão direito e selecione Salvar Link Como ) e salve em <code>C:\php5\php.ini</code>
		</li>
		<li>Abra o terminal <code>cmd.exe</code> <strong>( como administrador )</strong> e execute os
			seguintes comandos:
			<ul>
			  <li><code>SETX /M PATH "%PATH%;C:\php5"</code> para incluir a pasta onde o Interpretador do PHP reside
				  à Variável do Ambiente <strong>Path</strong></li>
			  <li><code>SETX /M PATHEXT "%PATHEXT%;.PHP"</code> para incluir a extensão .php à Variável do Ambiente
				  <strong>PathExt</strong></li>
			  <li><code>php -v</code> para verificar se a instalação do PHP foi bem sucedida</li>
			</ul>
		</li>
	  </ul>
	</li>
	<br />
	<li><h4>Módulo NortaoX Webservice Cliente</h4>
	  <ul>
		<li><a href="https://github.com/drupalista-br/nx_wsclient/archive/master.zip">Clique aqui</a> para
			baixar o módulo que irá comunicar-se com os Webservices da NortaoX.com. Descompacte o arquivo ZIP
			para a pasta <code>C:\nxwscliente</code>
		</li>
		<li>Acesse <a href="https://accounts.google.com/SignUp?service=mail">Gmail</a> e crie uma nova conta de
			email. Essa conta será usada como servidor SMTP para enviar notificações. Anote o usuário
			e senha pois você precisará logo mais abaixo.</li>
		<li>Abra o terminal <code>cmd.exe</code> e execute os seguintes comandos:
		  <ul>
			<li><code>cd C:\nxwscliente</code></li>
			<li><code>cli</code> para listar todos os comandos disponíveis</li>
			<li>Informe o ambiente de trabalho:<br />
				<code>cli ambiente sandbox</code> para comunicar-se com o ambiente de testes em
				<a href="http://loja.nortaoxsandbox.tk">loja.nortaoxsandbox.tk</a><br />
				<code>cli ambiente producao</code> para comunicar-se com o ambiente de produção em
				<a href="http://loja.nortaox.com">loja.nortaox.com</a>
			</li>
			<li>Informe o usuário e a senha do Lojista que você cadastrou no ambiente de testes em
				<a href="http://loja.nortaoxsandbox.tk">loja.nortaoxsandbox.tk</a> ou o usuário e a senha do
				ambiente de produção em <a href="http://loja.nortaox.com">loja.nortaox.com</a>:<br />
				<code>cli config credenciais username "MEU USUARIO"</code><br />
				<code>cli config credenciais password MINHASENHA</code></li>
			<li>Informe o usuário e a senha do servidor SMTP:<br />
				<code>cli config smtp username usuario@gmail.com</code><br />
				<code>cli config smtp password MINHASENHA</code></li>
			<li>Informe os emails das pessoas que irão receber notificações sobre falhas na sincronização de dados.
				Por exemplo, se o João e a Maria forem receber as notificações então execute:<br />
				<code>cli config notificar Joao joao@provedor.com</code><br />
				<code>cli config notificar Maria maria@provedor.com</code><br />
				Para excluir execute:<br />
				<code>cli config notificar Maria ""</code><br />
			</li>
			<li>Exemplo de como visualizar os detalhes das configurações salvas:<br />
				<code>cli config mostrar credenciais</code> para ver as credenciais do Lojista.<br />
				<code>cli config mostrar notificar</code> para ver os emails que serão notificados
				quando houver falha na sincronização de dados.
			</li>
			<li>Execute <code>cli testar</code> para verificar se as configurações estão corretas.</li>
		  </ul>
		</li>
	  </ul>
	</li>
  </ul>

</div>

<div class="ready-accordion">
  <span class="ready-accordion-header"><h3><u>Cadastrar Produtos</u></h3></span>
	<ul>
	  <li>A sua aplicação deverá criar um arquivo texto contendo os dados do(s) produto(s) a serem
		  cadastrados no website da NortaoX.com.
	  </li>
	  <li>O conteúdo do arquivo texto deverá ter estrutura de <a href="http://pt.wikipedia.org/wiki/INI_(formato_de_arquivo)">
		arquivo INI</a>. Por exemplo:<br />
		Nome do arquivo: <code>prod_520.txt</code><br />
		<script src="https://gist.github.com/drupalista-br/b7ae0c12846a4b9cfc95.js"></script>
		O exemplo acima cadastrará um único produto no website da NortaoX.com<br /><br />
		Nome do arquivo: <code>prod_520_521.txt</code><br />
		<script src="https://gist.github.com/drupalista-br/2cb37eaf477aa0a76ee3.js"></script>
		O exemplo acima cadastrará 2 produtos no website da NortaoX.com. <br />
		<strong>ATENÇÃO:</strong>
		não tente cadastrar centenas de produtos com este método, entre em contato com a NortaoX.com
		para fazer o cadastro inicial dos produtos no ambiente de produção diretamente no servidor
		da NortaoX.com.<br /><br />
		O campo:
		<ul>
		  <li><code>preco</code> é obrigatório, deverá conter o preço de venda do produto em centavos. Assim,
			<code>10760</code> equivale a <code>107,60</code>
		  </li>
		  <li><code>preco_velho</code> é opcional e será apresentado ao visitante do website da NortaoX.com
			em vermelho e riscado ao meio. Assim como <code>preco</code>, o valor deste campo, quando informado,
			também deverá  ser em centavos.
		  </li>
		  <li><code>qtde_em_estoque</code> é obrigatório, deverá informar quantas unidades do produto há em estoque.</li>
		  <li><code>cod_cidade</code> deverá conter o código da cidade onde o produto está localizado
			  fisicamente. Para ter acesso a uma lista atualizada de cidades e seus respectivos códigos,
			  abra o terminal <code>cmd.exe</code> e execute:
			  <ul>
				<li><code>C:\nxwscliente\cli consultar cidades</code></li>
				<li>O comando acima criará o arquivo <code>C:\nxwscliente\dados\consulta\cidades.txt</code></li>
			  </ul>
		  </li>
		  <li><code>cod_produto_erp</code> é opcional, entretanto altamente recomendado que seu valor seja
			  enviado, refere-se ao código de identificação do produto na sua aplicação.</li>
		  <li><code>localizacao_fisica</code> é opcional, refere-se ao endereço de localização do produto
			  no estoque do Lojista.</li>
		</ul>
	  </li>
	  <br />
	  <li>A sua aplicação deverá salvar os arquivos contendo os dados dos produtos em
		<code>C:\nxwscliente\dados\produto</code>. O nome e a extensão dos arquivos são irrelevantes, fica ao
		seu critério convencionar os nomes. Recomenda-se, no entanto, que faça uso da extensão .txt
	  </li>
	  <li>Caso queira trabalhar com a pasta <code>dados</code> em outro local, abra o terminal <code>cmd.exe</code>
		  e execute os seguintes comandos:<br />
		  <code>cd C:\nxwscliente</code><br />
		  <code>cli config pastas dados "C:\minha pasta\dados"</code> e <br />
		  <code>cli testar</code><br />
		  De agora em diante a sua aplicação deverá salvar os arquivos de dados em
		  <code>C:\minha pasta\dados\produto</code>
	  </li>
	</ul>
</div>

<div class="ready-accordion">
  <span class="ready-accordion-header"><h3><u>Atualizar Produtos</u></h3></span>
  <ul>
	<li>Assim como no cadastro de produtos, a sua aplicação deverá criar um arquivo texto estruturado no formato
		de <a href="http://pt.wikipedia.org/wiki/INI_(formato_de_arquivo)">arquivo INI</a> contendo os dados
		dos campos a serem atualizados
	</li>
	<li>O arquivo de dados deverá conter no mínimo 2 campos. Um campo para identificar o produto e os demais
		conterão valores a serem atualizados no sistema da NortaoX.com. Por exemplo: <br />
		Nome do arquivo: <code>prod_520.txt</code><br />
		<script src="https://gist.github.com/drupalista-br/6a217eddd7ebb6608d0d.js"></script>
		O exemplo acima irá atualizar a quantidade em estoque do produto sob o código 520.<br /><br />
		Nome do arquivo: <code>prod_520_521.txt</code><br />
		<script src="https://gist.github.com/drupalista-br/abb259fc7d3ac7be5c5a.js"></script>
		O exemplo acima irá atualizar a quantidade em estoque dos produtos sob os códigos 520 e 521.
	</li>
	<br />
	<li>Um produto poderá ser identificado de 3 formas. São elas:
	  <ul>
		<li><code>product_id</code> refere-se ao código de identificação do produto no sistema da NortaoX.com<br />
		Caso este campo exista no arquivo de dados, os demais campos de identificação ( sku e cod_produto_erp )
		serão ignorados. Mais detalhes sobre o este campo no passo <code>Sincronização de Dados</code>
		</li>
		<li><code><a href="http://pt.wikipedia.org/wiki/Stock_Keeping_Unit">sku</a></code> será o segundo campo
		de identificação do produto a ser buscado caso <code>product_id</code> não exista no arquivo de dados.
		Mais detalhes sobre o este campo no passo <code>Sincronização de Dados</code>
		</li>
		<li><code>cod_produto_erp</code> será o terceiro campo de identificação do produto  a ser buscado caso
		os campos <code>product_id</code> e <code>sku</code> não existam no arquivo de dados</li>
	  </ul>
	</li>
	<li>Os arquivos de dados contendo as atualizações de dados também deverão ser salvos em
		<code>C:\nxwscliente\dados\produto</code>
	</li>
  </ul>

</div>
<div class="ready-accordion">
  <span class="ready-accordion-header"><h3><u>Sincronização de Dados</u></h3></span>
  <ul>
	<li>Abra o <a href="http://windows.microsoft.com/pt-br/windows/schedule-task">Agendador de Tarefas</a>
	  do Windows e crie uma nova tarefa com as seguintes características:
	  <ul>
		<li>Nomeie a tarefa <code>NortaoX WS Cliente</code></li>
		<li>Configure para executar o arquivo <code>C:\nxwscliente\sincronizar.bat</code> a cada 45 minutos</li>
	  </ul>
	</li>
	<li>A sincronização de dados irá fazer o seguinte:
	  <ul>
		<li>Ler todos os arquivos presentes em <code>C:\nxwscliente\dados\produto</code> e:
		  <ul>
			<li>Cadastrará ou atualizará produtos no sistema da NortaoX.com via Webservice</li>
			<li>Moverá cada arquivo de dados para:
			  <ul>
				<li><code>C:\nxwscliente\tmp\sucessos\produto</code> quando a ação for bem sucedida</li>
				<li><code>C:\nxwscliente\tmp\falhas\produto</code> quando a ação falhar após 4 tentativas.
					Sendo uma tentativa a cada execução do Agendador de Tarefa</li>
				<li>O arquivo que for movido para <code>..\tmp\sucessos\produto</code> ou
				  <code>..\tmp\falhas\produto</code> conterá informações sobre a sincronização de cada item
				  ( produto ) presente no arquivo de dados. São elas:
				  <ul>
					<li>A quantidade de tentativas</li>
					<li>A data e hora da última tentativa</li>
					<li>A mensagem, emitida pelo Webservice, de status ou erro da última tentativa</li>
				  </ul>
				</li>
			  </ul>
			</li>
		  </ul>
		</li>
		<li>Quando a tentativa for bem sucedida, o arquivo movido para
		  <code>C:\nxwscliente\tmp\sucessos\produto</code>, além das informações sobre a sincronização,
		  também conterá o valor dos campos:
		  <ul>
			<li><code>product_id</code> referindo-se ao código de identificação do produto no sistema
			  da NortaoX.com</li>
			<li><code>sku</code> também poderá ser usado como identificador do produto no sistema
			  da NortaoX.com</li>
			<li><code>created</code> referindo-se a data e hora do cadastro do produto</li>
			<li><code>changed</code> referindo-se a data e hora da última atualização do produto</li>
		  </ul>
		</li>
		<li>Quando a tentativa falhar, será então enviado um email de notificação aos administradores do seu
			aplicativo e os detalhes da falha será registrado em um arquivo de log localizado em
			<code>C:\nxwscliente\tmp\logs\AAA-MM-DD.log</code>
		</li>
	  </ul>
	</li>
	<li>A sincronização NÃO será executada quando:
	  <ul>
		<li>Não houver conexão com a internet</li>
		<li>O website da NortaoX.com estiver inacessível</li>
	  </ul>
	  Cada tentativa de sincronizão que NÃO for executada será registrada em um arquivo de log localizado em
	  <code>C:\nxwscliente\tmp\logs\AAA-MM-DD.log</code><br />
	  As tentativas que NÃO forem executdas sob estas circunstâncias não contarão como falhas. Só será contado
	  como falha quando ouver uma comunicação efetiva com o Webservice e esta por sua vez falhar.
	</li>
	<li>Para executar a sincronização manualmente, abra <code>cmd.exe</code> e execute os seguintes comandos:<br />
		<code>cd C:\nxwscliente</code><br />
		<code>sincronizar</code><br />
	</li>
	<li>Caso queira trabalhar com a pasta <code>tmp</code> em outro local que não seja a pasta padrão, abra o
		terminal <code>cmd.exe</code> e execute os seguintes comandos:<br />
		<code>cd C:\nxwscliente</code><br />
		<code>cli config pastas tmp "C:\minha pasta\tmp"</code> e <br />
		<code>cli testar</code><br />
	</li>
  </ul>
</div>
<div class="ready-accordion">
  <span class="ready-accordion-header"><h3><u>Consultar Produto</u></h3></span>
  <ul>
	<li>Existem 3 formas para a sua aplicação consultar um produto no Website da NortaoX.com. São elas:
	  <ul>
		<li><code>php C:\nxwscliente\cli.php consultar produto product_id VALOR_DO_PRODUCT_ID</code> ou <br />
		  <code>php C:\nxwscliente\cli.php consultar produto sku VALOR_DO_SKU</code> ou <br />
		  <code>php C:\nxwscliente\cli.php consultar produto cod_produto_erp VALOR_DO_COD_PRODUTO_ERP</code>
		</li>
		<li>Em seguinda a sua aplicação deverá ler o arquivo<br />
		  <code>C:\nxwscliente\dados\consulta\produto_product_id_VALOR_DO_PRODUCT_ID.txt</code> ou <br />
		  <code>C:\nxwscliente\dados\consulta\produto_sku_VALOR_DO_SKU.txt</code> ou <br />
		  <code>C:\nxwscliente\dados\consulta\produto_cod_produto_erp_VALOR_DO_COD_PRODUTO_ERP.txt</code>
		</li>
	  </ul>
	</li>
	<li>Caso a consulta falhe, o evento será então registrado em <code>C:\nxwscliente\tmp\logs\AAA-MM-DD.log</code></li>
  </ul>
</div>
<div class="ready-accordion">
  <span class="ready-accordion-header"><h3><u>Consultar Pedido</u></h3></span>
  <ul>
	<li>Para consultar um pedito, a sua aplicação deverá executar
		<code>php C:\nxwscliente\cli.php pedido no NUMERO_DO_PEDIDO</code>
	</li>
	<li>Em seguinda a sua aplicação deverá ler o arquivo <code>C:\nxwscliente\dados\consulta\pedido_no_NUMERO_DO_PEDIDO.txt</code></li>
	<li>Caso a consulta falhe, o evento será então registrado em <code>C:\nxwscliente\tmp\logs\AAA-MM-DD.log</code></li>
  </ul>
</div>
<br />
<br />