/**
 * Executes the CDC calculation.
 *
 * @event onclick - when submit button is clicked.
 * @requires module:rational
 * @see https://api.jquery.com/click/
 * @see https://api.jquery.com/event.preventDefault/
 */
$("#submitButton").on("click", async function (event) {
  var errorMessage = "";
  if ($("#parc").val() <= 2) {
    errorMessage += "<p>Número de parcelas deve ser maior do que 1.</p>";
  }
  if ($("#itax").val() <= 0 && $("#ipp").val() <= 0) {
    errorMessage +=
      "<p>Taxa de juros e valor final não podem ser ambos nulos.</p>";
  }
  if ($("#itax").val() <= 0 && $("#ipv").val() <= 0) {
    errorMessage +=
      "<p>Taxa de juros e valor financiado não podem ser ambos nulos.</p>";
  }
  if ($("#ipv").val() <= 0 && $("#ipp").val() <= 0) {
    errorMessage +=
      "<p>Valor financiado e valor final não podem ser ambos nulos.</p>";
  }
  if ($("#inb").val() < 0 || +$("#inb").val() > $("#parc").val()) {
    errorMessage +=
      "<p>Meses a voltar deve ser positivo e menor ou igual ao número de parcelas.</p>";
  }
  event.preventDefault();
  if (errorMessage != "") {
    $("#errorMessage").html(errorMessage);
    $("#errorMessage").show();
    $("#successMessage").hide();
    return;
  } else {
    $("#successMessage").show();
    $("#errorMessage").hide();
  }

  // 1) Enviar dados para processamento no servidor

  // 1.1) Ler entrada do usuário
  let np = Number(parc.value); // representa o número de parcelas 
  let t = Number(itax.value); // representa a taxa de juros
  let pp = Number(ipp.value); // representa o valor final
  let pv = Number(ipv.value); // representa o valor financiado a vista
  let pb = Number(ipb.value); // representa o valor a voltar
  let nb = Number(inb.value); // representa o meses a voltar
  let dp = idp.checked; // representa a informação de se houve de parcela de entrada ou não
  let prt = iprt.checked; // representa a informação de se deve haver print ou não
  setDownPayment(dp);

  // 1.2) Preparar entrada do usuário para envio

  let input = {
    "np" : np, // representa o número de parcelas 
    "t" : t, // representa a taxa de juros
    "pp" : pp, // representa o valor final
    "pv" : pv, // representa o valor financiado a vista
    "pb" : pb, // representa o valor a voltar
    "nb" : nb, // representa o meses a voltar
    "dp" : dp, // representa a informação de se houve de parcela de entrada ou não
    "prt" : prt// representa a informação de se deve haver print ou não
  }

  // 1.3) Enviar requisição para servidor

  let result = await fetch("api/index.php", {
    "method": "POST",
    "headers": {
        "Content-Type": "application/json; charset=utf-8"
    },
    "body": JSON.stringify(input)
  }).then(function(response) {
    return response.json();
  })
  
  console.log("Result = ");
  console.log(result); 

  np = Number(result["np"]); // representa o número de parcelas 
  t = Number(result["t"]); // representa a taxa de juros
  pp = Number(result["pp"]); // representa o valor final
  pv = Number(result["pv"]); // representa o valor financiado a vista
  pb = Number(result["pb"]); // representa o valor a voltar
  nb = Number(result["nb"]);// representa o meses a voltar
  dp = result["dp"]; // representa a informação de se houve de parcela de entrada ou não
  prt = result["prt"]; // representa a informação de se deve haver print ou não
  let cf = Number(result["cf"]); // representa o coeficiente de financiamento
  let pmt = Number(result["pmt"]); // representa o valor de cada parcela a pagar
  let price = result["ptb"]; // representa a Tabela PRICE
  let valorCorrigido = Number(result["corrigido"]); // representa o valor corrigido
  let taxaReal = Number(result["taxaReal"]); // representa a taxaReal


  // Formatar tabela price recebida como string
   console.log(price);
  let ptb = []; 
    for(let i in price) { 
      if (i > 0) {
        for(let j in price[i]) {
          price[i][j] = Number(price[i][j]);
          console.log(price[i][j]);
        }
        ptb.push(price[i]); 
        console.log(price[i]);
      }
  };  

  $("#greenBox").show();
  $("#blueBox").show();
  $("#redBox").show();
  $("#cdcfieldset").hide();

  $("#greenBox").html(
    `<h4>Parcelamento: ${dp ? "1+" : ""}${np} meses</h4>
    <h4>Taxa: ${(100 * t).toFixed(2)}% ao mês = ${(
      ((1 + t) ** 12 - 1) *
      100.0
    ).toFixed(2)}% ao ano</h4>
    <h4>Valor Financiado: \$${pv.toFixed(2)}</h4>
    <h4>Valor Final: \$${pp.toFixed(2)}</h4>
    <h4>Valor a Voltar: \$${pb.toFixed(2)}</h4>
    <h4>Meses a Voltar: ${nb}</h4>
    <h4>Entrada: ${dp}</h4>`
  );

$("#blueBox").html(
  `<h4>Coeficiente de Financiamento: ${cf.toFixed(6)}</h4>
  <h4>Prestação: ${cf.toFixed(6)} * \$${pv.toFixed(2)} = \$${pmt.toFixed(
    2
  )} ao mês</h4>
  <h4>Valor Pago com Juros: \$${ptb.slice(-1)[0][1].toFixed(2)}</h4>
  <h4>Taxa Real (0 iterações): ${taxaReal}% ao mês</h4>
  <h4>Valor Corrigido: \$${valorCorrigido}</h4>`
);

$("#redBox").html(htmlPriceTable(ptb));

if (prt) {
  window.print();
}
});

/**
 * Go back to the form.
 *
 * @event onclick - when the green box rectangle is clicked.
 * @see https://api.jquery.com/click/
 * @see https://api.jquery.com/event.preventDefault/
 */
$("#greenBox").on("click", function (event) {
$("#greenBox").hide();
$("#blueBox").hide();
$("#redBox").hide();
$("#cdcfieldset").show();
});


// Author: Profº Paulo Roma
const pt = {
  lenNum: 13,
  lenMes: 6,
  precision: 2,
  eol: "|",
  filler: " ",
};

// Author: Profº Paulo Roma
export function setDownPayment(dp = true) {
  setDownPayment.downP = dp;
}

// Author: Profº Paulo Roma
export function htmlPriceTable(ptb) {
  let table = `<table border=1>
      <caption style='font-weight: bold; font-size:200%;'>
        Tabela Price
      </caption>
      <tbody style='text-align:center;'>
    `;
  ptb.forEach((row, i) => {
    table += "<tr>";
    row.forEach((col, j) => {
      if (typeof col === "number") {
        if (j > 0) col = col.toFixed(j == 2 ? pt.precision + 1 : pt.precision);
      }
      table += i > 0 ? `<td>${col}</td>` : `<th>${col}</th>`;
    });
    table += "</tr>";
  });
  table += "</tbody></table>";

  return table;
}