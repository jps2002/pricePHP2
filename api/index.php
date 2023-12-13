<?php declare(strict_types=1);

    if(isset($_POST)) {
    $data = file_get_contents("php://input");
    $input = json_decode($data, true);
    // echo json_encode(priceTable($user["np"], $user["pv"], $user["t"], $user["pmt"], $user["dp"])); }
    

    // Obter entradas do usuário
    /* $numeroParcelas = (int) $_POST["np"];
    $juros = (float) $_POST["tax"];
    $valorFinanciado = (float) $_POST["pv"];
    $valorFinal = (float) $_POST["pp"];
    $mesesAVoltar = (int) $_POST["pb"];
    $temEntrada = (bool) $_POST["dp"]; */

    /* "np" : np, // representa o número de parcelas 
      "t" : t, // representa a taxa de juros
      "pp" : pp, // representa o valor final
      "pv" : pv, // representa o valor financiado a vista
      "pb" : pb, // representa o valor a voltar
      "nb" : nb, // representa os meses a voltar
      "dp" : dp, // representa a informação de se houve de parcela de entrada ou não
      "prt" : prt// representa a informação de se deve haver print ou não */

    // FUNÇÕES DE UTILIDADE 


    function converterJurosMensalParaAnual(float $juros):float{
        $jurosTemp =  $juros /= 100;
        $resultado = (pow(1 + $jurosTemp, 12) - 1) * 100;
        return numberToFixed($resultado, 2);
    }

    /*Retorna um número com decimals casas decimais */
    function numberToFixed(float $num, int $decimals):float{
        return (float) number_format($num, $decimals, '.', "");
    }
    
    /*Retorna um número com decimals casas decimais */
    function toFixed(float $num,int $decimals):string{
        return number_format($num, $decimals, '.', "");
    }
    
    /* Retorna a tabela PRICE */
    function getTabelaPrice(float $precoAVista,float $pmt,$numParcelas,float $taxaDeJuros,bool $temEntrada):array {

        $jurosTotal = 0;
        $amortizacaoTotal = 0;
        $totalPago = $temEntrada? $pmt : 0;
    
        $tabelaPrice = array(array("Mês","Prestação", "Juros", "Amortizacao","Saldo Devedor"));
    
    
        $juros = $taxaDeJuros; 
        $amortizacao = 0;  
        $saldoDevedor = $precoAVista;
    
    
        for($i = 1; $i <= $numParcelas; $i++){
    
    
            $juros = ($saldoDevedor * $taxaDeJuros);
    
            $amortizacao = ($pmt - $juros);
    
            $saldoDevedor -=  $amortizacao;
            
            $saldoDevedor = $saldoDevedor > 0 ? $saldoDevedor : 0;
           
            array_push($tabelaPrice, array($i ,toFixed($pmt,2) , toFixed($juros,3), toFixed($amortizacao,2), toFixed($saldoDevedor,2)));
    
            $jurosTotal +=  $juros;
            $totalPago += $pmt;
            $amortizacaoTotal += $amortizacao;
    
        }
    
        $totalPago = toFixed($totalPago,2);
        $jurosTotal = toFixed($jurosTotal,3);
        $amortizacaoTotal = toFixed($amortizacaoTotal,2);
        $saldoDevedorStr = toFixed($saldoDevedor,2);
    
        array_push($tabelaPrice, array("Total:", "{$totalPago}","{$jurosTotal}", "{$amortizacaoTotal}","{$saldoDevedorStr}" ) );
    
        return $tabelaPrice;
    }

    /* Retorna o valor a voltar */
    function getValorCorrigido(array $tabelaPrice,int $numeroParcelas,int $mesesAVoltar):float{
        $mesesAVoltar = (int) $mesesAVoltar;
        if ($mesesAVoltar == 0 || $mesesAVoltar >= $numeroParcelas){
            return 0;
        }
        else {
            $tamanho = count($tabelaPrice) - 2;
            return (float) $tabelaPrice[$tamanho - $mesesAVoltar ][4];
        }
    }

    /* Retorna f(e) = 1 + t ou 1, caso haja uma entrada, ou não, respectivamente */
    function fe(bool $temEntrada,float $taxaJuros):float{
        return  ($temEntrada)?  1 + $taxaJuros : 1;
    }
    
    /* */
    function calcularFatorAplicado(bool $temEntrada,int $numParcelas,float $coeficienteFinanciamento,float $taxaJuros):float{
        $f = fe($temEntrada, $taxaJuros);
        
      //  $f = ($temEntrada)?  1 + $taxaJuros : 1 // fator
    
        return (float) $f/($numParcelas * $coeficienteFinanciamento);
    }


    /* Retorna o valor futuro(preço ao final do pagamento financiado) */
    function getValorFuturo(float $coeficienteFinanciamento,float $taxaJuros,float $precoAVista,int $parcelas,bool $temEntrada):float{

        $resultado = $precoAVista / calcularFatorAplicado($temEntrada,$parcelas,$coeficienteFinanciamento,$taxaJuros);
    
        return numberToFixed($resultado,2);
    }
    

    /* Retorna o coeficiente de financiamento (valor da parcela a ser paga por cada unidade monetária
       que está sendo tomada emprestada) */
    function getCF(float $taxaJuros,int $quantidadeParcelas):float{
        $taxaCorrigida = ($taxaJuros > 1) ? $taxaJuros / 100 : $taxaJuros;    
        return $taxaCorrigida / (1 - pow(1 + $taxaCorrigida, $quantidadeParcelas * -1) );
    }
    
    /* Retorna o valor de cada parcela individual a pagar */
    function getPmt(float $precoAVista,float $coeficienteFinanciamento):float{
        return $precoAVista * $coeficienteFinanciamento;
    }

    /* Avalia a função que calcula taxa de juros */
    function getValorJuros(float $precoAPrazo,float $taxaDeJuros,float $precoAVista,bool $temEntrada,int $numParcelas):float{
        $a = 0; 
        $b = 0; 
        $c = 0;

        if ($temEntrada) {
            $a = pow(1 + $taxaDeJuros, $numParcelas - 2);
            $b = pow(1 + $taxaDeJuros, $numParcelas - 1);
            $c = pow(1 + $taxaDeJuros, $numParcelas);
    
            return ($precoAVista * $taxaDeJuros * $b) - ($precoAPrazo/$numParcelas * ($c - 1));
           
        }
        else {
            $a = pow(1 + $taxaDeJuros, -$numParcelas);
            $b = pow(1 + $taxaDeJuros, -$numParcelas - 1 );
    
            return ($precoAVista * $taxaDeJuros) - ( ($precoAPrazo / $numParcelas) * (1 - $a) ); 
        }
    }
    
    /* Avalia a derivada da função que calcula taxa de juros */
    function getDerivadaJuros(float $precoAPrazo,float $taxaDeJuros,float $precoAVista,bool $temEntrada,int $numParcelas):float{
        $a = 0; $b = 0; $c = 0;
        if($temEntrada){
                $a = pow(1+$taxaDeJuros,$numParcelas-2);
                $b = pow(1 + $taxaDeJuros, $numParcelas - 1);
    
                return $precoAVista * ($b + ($taxaDeJuros * $a * ($numParcelas - 1) ) ) - ($precoAPrazo * $b);
               
            }
            else{
                $a = pow(1 + $taxaDeJuros, -$numParcelas);
                $b = pow(1 + $taxaDeJuros, -$numParcelas - 1 );
        
                return $precoAVista - ($precoAPrazo * $b); 
            }
    }

    /* Retorna a taxa de juros no intervalo [0, 1] utilizando o método de newton */
    function getJuros(float $precoAVista,float  $precoAPrazo,int $numParcelas,bool $temEntrada):float {
        $tolerancia = 0.0001;  
    $taxaDeJuros = 0.1; // Palpite inicial
    $taxaDeJurosAnterior = 0.0;


    $funcao = 0; $derivada = 0;
    $iteracao = 0;

    
    while(abs($taxaDeJurosAnterior - $taxaDeJuros ) >= $tolerancia){
        
        $taxaDeJurosAnterior = $taxaDeJuros;
        $funcao = calcularValorFuncao($precoAPrazo,$taxaDeJuros,$precoAVista,$temEntrada,$numParcelas);

        $derivada = calcularValorDerivadaFuncao($precoAPrazo,$taxaDeJuros,$precoAVista,$temEntrada,$numParcelas);

        $taxaDeJuros = $taxaDeJuros - ($funcao / $derivada);

        $iteracao++;
    }

   
    return $taxaDeJuros;
    }


    // 1) Ler dados de entrada do cliente

    $numeroParcelas = (int) $input["np"];
    $juros = (float) $input["t"];
    $valorFinanciado = (float) $input["pv"];
    $valorFinal = (float) $input["pp"];
    $mesesAVoltar = (int) $input["nb"];
    $temEntrada = (bool) $input["dp"];

    $tabelaPrice; 
    $valorCorrigido;
    $coeficienteFinanciamento;
    $pmt;

    

    // 2) Processar entrada e calcular valores  

    // 2.1) Obter taxa de juros 

    // Se foi dado taxas de juros, mas não valor final:  
    if ($juros != 0 && $valorFinal == 0) {
       // Garantir que taxa de juros está no formato correto 
        $juros /= 100;
    } else { // Se não foi dado taxas de juros, mas foi dado valor final: 
        // Estimar usando método de newton
         $juros = getJuros($valorFinanciado,$valorFinal,$numeroParcelas,$temEntrada);
        //[$juros, $numeroIteracoes] = getJuros($valorFinanciado,$valorFinal,$numeroParcelas,$temEntrada);
    }

    // 2.2) Obter coeficiente de financiamento
    
    $coeficienteFinanciamento = getCF($juros, $numeroParcelas);

    // 2.3) Obter valor final 

    // Se não foi dado valor final:
    if ( $valorFinal == 0) {
        // Calcular valor final
        $valorFinal = getValorFuturo($coeficienteFinanciamento,$juros,$valorFinanciado,$numeroParcelas,$temEntrada);
    } 

    // 2.4) Obter valor das parcelas individuais 

    // Calcular valor de parcelas individuais sem entrada
    $pmt = getPmt($valorFinanciado,$coeficienteFinanciamento);

    // Se foi paga uma parcela de entrada:
    if ($temEntrada) {
        // Ajustar valor das parcelas a pagar
        $pmt /= 1 + $juros;
        // Ajustar quantidade de parcelas a pagar
        $numeroParcelas--;
        // Ajustar o valor total financiado
        $valorFinanciado -= $pmt; // Novo valor = Preço a vista menos entrada
    }

    // 2.5) Obter Tabela PRICE
    $tabelaPrice = getTabelaPrice($valorFinanciado,$pmt,$numeroParcelas,$juros,$temEntrada);

    // 2.6) Obter valor corrigido 
    $valorCorrigido = getValorCorrigido($tabelaPrice,$numeroParcelas,$mesesAVoltar);

    // 2.7) Obter taxa real
    $taxaReal = numberToFixed($juros * 100, 4);


    // 3) Retornar valores calculados ao cliente
    
    // Empacotar valores calculados 
    $responseObj = [
        "np" => $numeroParcelas, // representa o número de parcelas 
        "t" => $juros, // representa a taxa de juros
        "pp" => $valorFinal, // representa o valor final
        "pv" => $valorFinanciado, // representa o valor financiado a vista
        "pb" => $input["pb"], // representa o valor a voltar
        "nb" => $mesesAVoltar, // representa o meses a voltar
        "dp" => $temEntrada, // representa a informação de se houve de parcela de entrada ou não
        "prt" => $input["prt"],// representa a informação de se deve haver print ou não
        "ptb" => $tabelaPrice, // representa a Tabela PRICE calculada
        "corrigido" => $valorCorrigido,
        "cf" => $coeficienteFinanciamento,
        "pmt" => $pmt,
        "taxaReal" => $taxaReal, 
    ];
    
    // $response = array($numeroParcelas, $juros, $valorFinanciado, $valorFinal, $mesesAVoltar, $temEntrada, $tabelaPrice, $valorCorrigido, $coeficienteFinanciamento, $pmt); 
    //echo json_encode($response);

    // Enviar resposta ao cliente
    echo json_encode($responseObj);
}

?>


