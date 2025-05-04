<?php
class DBconect {
    private $connection;

    public function __construct($server, $user, $senha, $db) {
        $this->connection = new mysqli($server, $user, $senha, $db);

        if ($this->connection->connect_error) {
            die("Falhou ao conectar MySQL: " . $this->connection->connect_error);
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    public function closeConnection() {
        $this->connection->close();
    }
}
?>
