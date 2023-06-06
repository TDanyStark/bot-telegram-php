c<?php

// Importo las variables de configuración de la base de datos y del bot
include("db.php");

// Dentro de db.php se encuentra la siguiente línea: $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
// ya la conexión está establecida, por lo que no es necesario volver a establecerla

// Verificar si la conexión fue exitosa
if (!$conn) {
    die('Error al conectar a la base de datos: ' . mysqli_connect_error());
} 

$API_BOT = API_BOT;
$website = "https://api.telegram.org/bot".$API_BOT;

$input = file_get_contents("php://input");
$update = json_decode($input, TRUE);

$chatId = $update["message"]["chat"]["id"];
$message = $update["message"]["text"];

switch($message) {
    case "/test":
        sendMessage($chatId, "test");
        break;
    case "/hi":
        sendMessage($chatId, "hola ".$update["message"]["from"]["first_name"]." ".$update["message"]["from"]["last_name"]);
        break;
    case strpos($message, '/c') === 0:
        $dates = explode(' ', $message);
        $value = $dates[1];
        $descripcion = implode(' ', array_slice($dates, 2));

        // quitar los puntos y las comas
        $value = str_replace(".", "", $value);
        $value = str_replace(",", "", $value);

        // validar si el valor es un número
        if (!is_numeric($value)) {
            sendMessage($chatId, "El valor debe ser un número el formato es EJEMPLO: /c 1000");
            break;
        }
        saveAccount($value, $descripcion, $update);
        sendMessage($chatId, "Cuenta guardada: " . $value. " " . $descripcion);
        break;
    case strpos($message, '/sumar') === 0:
        $dates = explode(' ', $message);
        $startDate = $dates[1];
        $endDate = $dates[2];
        $parametro = $dates[3];

        if (count($dates) < 3) {
            sendMessage($chatId, "El formato es /sumar fecha_inicio fecha_fin EJEMPLO: /sumar 2020-01-01 2020-01-31");
            break;
        }

        // comprobar si la fecha es válida y si la fecha de inicio es menor a la fecha de fin
        if (strtotime($startDate) === false || strtotime($endDate) === false || strtotime($startDate) > strtotime($endDate)) {
            sendMessage($chatId, "El formato es /sumar fecha_inicio fecha_fin EJEMPLO: /sumar 2020-01-01 2020-01-31");
            break;
        }


        $total = sumAccounts($chatId, $startDate, $endDate);

        // pasar el total a formato de moneda
        $total = number_format($total, 0, ",", ".");

        sendMessage($chatId, "La suma de la cuenta en las fechas es: $ " . $total, false);

        if ($parametro == "d") {
            $arrayData = traer_descripciones($chatId, $startDate, $endDate);

            $message = "Descripciones: \n";
            foreach ($arrayData as $data) {
                $message .= $data["descripcion"] . ": $ " . number_format($data["valor"], 0, ",", ".") ." - ".$data["fecha"]. "\n"."______________________\n";
            }

            sendMessage($chatId, $message);
        }

        break;
    default:
        sendMessage($chatId, "Comando no reconocido");
        break;
}

function saveAccount($value, $descripcion, $update) {
    global $conn;

    $chatId = $update["message"]["chat"]["id"];
    $firstName = isset($update["message"]["from"]["first_name"]) ? $update["message"]["from"]["first_name"] : "desconocido";
    $lastName = isset($update["message"]["from"]["last_name"]) ? $update["message"]["from"]["last_name"] : "desconocido";


    // validar si el chatId ya existe en la base de datos en la tabla usuarios
    $sql = "SELECT * FROM usuarios WHERE chat_id = " . $chatId;
    $result = mysqli_query($conn, $sql);

    $respuesta = "";
    if (mysqli_num_rows($result) > 0) {
        // insertar el valor en la tabla cuentas con chat_id
        $sql = "INSERT INTO cuentas (chat_id, valor, descripcion, fecha) VALUES (" . $chatId . ", " . $value . ", '" . $descripcion . "', NOW())";
        mysqli_query($conn, $sql);

    } else {
        // insertar el nuevo usuario
        $sql = "INSERT INTO usuarios (chat_id, primer_nombre, segundo_nombre) VALUES (" . $chatId . ", '" . $firtsName . "', '" . $lastName . "')";
        mysqli_query($conn, $sql);

        $sql = "INSERT INTO cuentas (chat_id, valor, descipcion, fecha) VALUES (" . $chatId . ", " . $value . ", '" . $descripcion . "', NOW())";
    }
}

function sumAccounts($chatId, $startDate, $endDate) {
    global $conn;

    $sql = "SELECT SUM(valor) AS total FROM cuentas WHERE chat_id = " . $chatId . " AND fecha BETWEEN '" . $startDate . "' AND '" . $endDate . "'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row["total"];
    } else {
        return 0;
    }
}

function traer_descripciones($chatId, $startDate, $endDate) {
    global $conn;

    $sql = "SELECT descripcion, valor, DATE(fecha) AS fecha FROM cuentas WHERE chat_id = " . $chatId . " AND fecha BETWEEN '" . $startDate . "' AND '" . $endDate . "'";
    $result = mysqli_query($conn, $sql);

    $arrayData = array();
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $arrayData[] = $row;
        }
    }

    return $arrayData;
}

function sendMessage($chatId, $message, $delete = true) {
    $url = $GLOBALS['website']."/sendMessage?chat_id=".$chatId."&parse_mode=HTML"."&text=".urlencode($message);
    $response = file_get_contents($url);

    // Obtén el ID del mensaje enviado desde la respuesta de la API de Telegram
    $responseData = json_decode($response, true);
    $messageId = $responseData['result']['message_id'];

    // Si la variable $delete es verdadera, elimina el mensaje después de 30 segundos
    if ($delete) {
        sleep(5);
        deleteMessage($chatId, $messageId);
    }
}

// Función para eliminar el mensaje utilizando la API de Telegram
function deleteMessage($chatId, $messageId) {
    $url = $GLOBALS['website']."/deleteMessage?chat_id=".$chatId."&message_id=".$messageId;
    file_get_contents($url);
}
?>