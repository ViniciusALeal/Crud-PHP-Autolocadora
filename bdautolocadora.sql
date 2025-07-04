-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/02/2023 às 16:30
-- Versão do servidor: 10.1.36-MariaDB
-- Versão do PHP: 7.2.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `bdautolocadora`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `tbcliente`
--

CREATE TABLE `tbcliente` (
  `cliente_cpf` char(11) NOT NULL,
  `cliente_nome` varchar(100) DEFAULT NULL,
  `cliente_endereco` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Fazendo dump de dados para tabela `tbcliente`
--

INSERT INTO `tbcliente` (`cliente_cpf`, `cliente_nome`, `cliente_endereco`) VALUES
('00967465732', 'José', 'Avenida 5, 476'),
('12343456578', 'Beatriz', 'Rua 45, 998'),
('89745678933', 'Ana', 'Rua 145, 1276'),
('93845768902', 'Carla', 'Rua 23, 9898');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tblocacao`
--

CREATE TABLE `tblocacao` (
  `locacao_codigo` int(11) NOT NULL,
  `locacao_veiculo` varchar(7) DEFAULT NULL,
  `locacao_cliente` char(11) DEFAULT NULL,
  `locacao_data_inicio` date DEFAULT NULL,
  `locacao_data_fim` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Fazendo dump de dados para tabela `tblocacao`
--

INSERT INTO `tblocacao` (`locacao_codigo`, `locacao_veiculo`, `locacao_cliente`, `locacao_data_inicio`, `locacao_data_fim`) VALUES
(1, 'ABC1234', '00967465732', '2022-01-10', '2022-01-24'),
(2, 'XXX0000', '93845768902', '2022-02-01', '2022-02-08'),
(3, 'ABC1234', '00967465732', '2022-02-05', '2022-02-12'),
(4, 'XYZ9999', '89745678933', '2022-02-01', '2022-02-08'),
(5, 'XXX0000', '12343456578', '2022-02-01', '2022-02-28');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tbmarca`
--

CREATE TABLE `tbmarca` (
  `marca_codigo` int(11) NOT NULL,
  `marca_descricao` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Fazendo dump de dados para tabela `tbmarca`
--

INSERT INTO `tbmarca` (`marca_codigo`, `marca_descricao`) VALUES
(1, 'Chevrolet'),
(2, 'Renault');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tbveiculo`
--

CREATE TABLE `tbveiculo` (
  `veiculo_placa` varchar(7) NOT NULL,
  `veiculo_marca` int(11) DEFAULT NULL,
  `veiculo_descricao` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Fazendo dump de dados para tabela `tbveiculo`
--

INSERT INTO `tbveiculo` (`veiculo_placa`, `veiculo_marca`, `veiculo_descricao`) VALUES
('ABC1234', 1, 'ONIX'),
('XXX0000', 2, 'SANDERO'),
('XYZ9999', 1, 'CRUZE');

--
-- Índices de tabelas apagadas
--

--
-- Índices de tabela `tbcliente`
--
ALTER TABLE `tbcliente`
  ADD PRIMARY KEY (`cliente_cpf`);

--
-- Índices de tabela `tblocacao`
--
ALTER TABLE `tblocacao`
  ADD PRIMARY KEY (`locacao_codigo`),
  ADD KEY `FK_tbLocacao_1` (`locacao_cliente`),
  ADD KEY `locacao_veiculo` (`locacao_veiculo`,`locacao_data_fim`);

--
-- Índices de tabela `tbmarca`
--
ALTER TABLE `tbmarca`
  ADD PRIMARY KEY (`marca_codigo`);

--
-- Índices de tabela `tbveiculo`
--
ALTER TABLE `tbveiculo`
  ADD PRIMARY KEY (`veiculo_placa`),
  ADD KEY `FK_tbVeiculo_1` (`veiculo_marca`);

--
-- Restrições para dumps de tabelas
--

--
-- Restrições para tabelas `tblocacao`
--
ALTER TABLE `tblocacao`
  ADD CONSTRAINT `FK_tbLocacao_1` FOREIGN KEY (`locacao_cliente`) REFERENCES `tbcliente` (`cliente_cpf`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_tbLocacao_2` FOREIGN KEY (`locacao_veiculo`) REFERENCES `tbveiculo` (`veiculo_placa`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `tbveiculo`
--
ALTER TABLE `tbveiculo`
  ADD CONSTRAINT `FK_tbVeiculo_1` FOREIGN KEY (`veiculo_marca`) REFERENCES `tbmarca` (`marca_codigo`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
