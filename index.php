<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>NeoAssist - Case aplicação técnica</title>

  <!-- Importando bootstrap -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <link href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" crossorigin="anonymous">

  <!--Importando datatable de https://datatables.net/ -->
  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>


</head>


<body>
  <div class="container-fluid">
    <div class="row">
      <div class="card w-100">
        <div class="card-header">Tickets NeoAssist</div>
        <div class="card-body">
          <table class='table table-hover table-striped text-center' id='ticketsTable'>
            <thead class='table-dark text-white'>
              <tr>
                <td>ID Ticket</td>
                <td>Cliente</td>
                <td>Assunto do ticket</td>
                <td>Data de criação</td>
                <td>Data de atualizaçao</td>
                <td>Pontuação</td>
                <td>Prioridade</td>
              </tr>
            </thead>
            <tbody>
              <?php
              //Abrindo e inserindo json em um array
              $data = file_get_contents("tickets.json");
              $jsonTickets = json_decode($data, true);

              //Palavras que podem indicar insatisfação do consumidor e exigir alta prioridade no ticket
              $palavrasChave = ["reclama", "reclameaqui", "procon", "problema", "providências" , "não receb", "contato", "errado", "solução", "insatisfeito"];
              $table = '';

              foreach ($jsonTickets as $key => $tickets){

                $ticketId = $tickets["TicketID"];
                $nome = $tickets["CustomerName"];
                $dataCriada = date("d/m/Y", strtotime($tickets["DateCreate"]));
                $dataAtualizada = date("d/m/Y", strtotime($tickets["DateUpdate"]));
                $assunto = $tickets["Interactions"][0]["Subject"];

                //Tickets começam com a prioridade normal e pontuação 0
                $prioridade = "Normal";
                $pontuacao = 0;
                foreach ($tickets["Interactions"] as $message) {

                  if($message["Sender"] == 'Customer'){
                    //Percorre somente as mensagens do cliente procurando palavras-chave 
                    //e pontua o ticket por cada palavra encontrada
                    foreach ($palavrasChave as $palavrasChave2) {
                      $buscaAssunto = strpos(strtolower($message["Subject"]),$palavrasChave2);
                      $buscaTitulo = strpos(strtolower($message["Message"]),$palavrasChave2);
                      //Se a funçao strpos retornar qualquer coisa diferente de falso ela encontrou alguma palavra
                      if($buscaAssunto !== false)
                        $pontuacao++;
                      if($buscaTitulo !== false)
                        $pontuacao++;
                    }
                    //Pega a data da última mensagem
                    $dataFinal = strtotime($message["DateCreate"]);

                  }
                  //Adiciona pontuação se o ticket está aberto a mais de 30 dias
                  //Só compara datas se a ultima mensagem for do cliente
                  if($tickets["DateCreate"] == $tickets["DateUpdate"]){
                    $dataAtualizada = "Nao respondido";
                  }else if(($dataFinal - strtotime($tickets["DateCreate"]))/86400 > 30){
                    $pontuacao++;
                  }
                }
                //Classifica ticket de acordo com sua pontuação
                if($pontuacao > 0){
                  $prioridade = "Alta";
                  $alerta = "badge-danger";
                } else {
                  $alerta = "badge-success";
                }

                //Monta array da tabela, adicionando no array a cada iteração
                $table = "$table  <tr>
                          <td>$ticketId</td>
                          <td>$nome</td>
                          <td>$assunto</td>
                          <td>$dataCriada</td>
                          <td>$dataAtualizada</td>
                          <td>$pontuacao</td>
                          <td><span class='badge $alerta'>$prioridade</span></td>
                          </tr>";

                //Adiciona a prioridade e pontuação no array do json
                $jsonTickets[$key]['Priority'] = $prioridade;
                $jsonTickets[$key]['Points'] = $pontuacao;

              }
              //Printa a tabela
              print($table);

              //Salva as modificações em um novo arquivo JSON, poderia também sobreescrever o json existente.
              $file = 'tickets_new';
              if (!file_exists($file)) {
                  $newJson = json_encode($jsonTickets, JSON_UNESCAPED_UNICODE);
                  file_put_contents('tickets_new.json', $newJson);
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

<script>
//Função do jQuery Datatable para montar a tabela
$(document).ready( function () {
  $('#ticketsTable').DataTable();
} );
</script>
