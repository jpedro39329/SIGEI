-- SIGEI - Dump Oficial Atualizado (Exclusivo URE 1 - Diretoria de Ensino de Bragança Paulista)
-- Compatível com MariaDB 10.4+ / 10.11+ e MySQL 8+
-- Gerado para: wmshpicv_SIGEI

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- DROP TABLE ordenado de todas as tabelas
DROP TABLE IF EXISTS `relatorios`;
DROP TABLE IF EXISTS `associacoes`;
DROP TABLE IF EXISTS `laudos`;
DROP TABLE IF EXISTS `notificacoes_lidas`;
DROP TABLE IF EXISTS `termos_aceite`;
DROP TABLE IF EXISTS `recuperacao_senha`;
DROP TABLE IF EXISTS `auditoria`;
DROP TABLE IF EXISTS `alunos`;
DROP TABLE IF EXISTS `usuarios_ue`;
DROP TABLE IF EXISTS `usuarios_pae`;
DROP TABLE IF EXISTS `usuarios_supervisor`;
DROP TABLE IF EXISTS `usuarios_ure`;
DROP TABLE IF EXISTS `empresa_ure`;
DROP TABLE IF EXISTS `empresas`;
DROP TABLE IF EXISTS `unidades_escolares`;
DROP TABLE IF EXISTS `unidades_regionais`;
DROP TABLE IF EXISTS `seduc`;
DROP TABLE IF EXISTS `admin`;

CREATE TABLE IF NOT EXISTS `admin` (
  `id_admin` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `cpf` char(11) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `uk_admin_cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin` (`id_admin`, `nome`, `cpf`, `senha`, `data_cadastro`) VALUES
(1, 'Ricardo Augusto Nogueira', '83918234150', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', '2026-09-24 09:00:00');

CREATE TABLE IF NOT EXISTS `seduc` (
  `id_seduc` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `cpf` char(11) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `setor` varchar(100) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_seduc`),
  UNIQUE KEY `uk_seduc_cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `seduc` (`id_seduc`, `nome`, `cpf`, `senha`, `setor`, `cargo`, `email`, `telefone`, `ativo`, `data_cadastro`) VALUES
(1, 'Mariana Siqueira Fontes', '48201938501', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'Coordenadoria Pedagógica (COPED)', 'Diretora de Centro de Educação Especial', 'mariana.fontes@educacao.sp.gov.br', '(11) 3218-2000', 1, '2026-09-24 09:00:00'),
(2, 'Carlos Alberto Medeiros', '91028475619', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'Departamento de Modalidades Educacionais', 'Especialista em Gestão Educacional', 'carlos.medeiros@educacao.sp.gov.br', '(11) 3218-2150', 1, '2026-09-24 09:00:00');

CREATE TABLE IF NOT EXISTS `unidades_regionais` (
  `id_ure` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `uge` varchar(20) DEFAULT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_ure`),
  UNIQUE KEY `uk_ure_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `unidades_regionais` (`id_ure`, `nome`, `uge`, `codigo`, `endereco`, `numero`, `bairro`, `municipio`, `cidade`, `cep`, `telefone`, `email`, `data_cadastro`) VALUES
(1, 'Diretoria de Ensino de Bragança Paulista', '081240', 'DE-BP', 'Avenida José Gomes da Rocha Leão', '450', 'Centro', 'Bragança Paulista', 'Bragança Paulista', '12900-300', '(11) 4034-7100', 'debraganca@educacao.sp.gov.br', '2026-09-24 09:00:00');

CREATE TABLE IF NOT EXISTS `unidades_escolares` (
  `id_ue` int(11) NOT NULL AUTO_INCREMENT,
  `cie` varchar(20) NOT NULL,
  `ua` varchar(20) DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `modalidade` varchar(100) DEFAULT NULL,
  `tipo_ue` enum('ESCOLA','CENTRO','OUTRO') DEFAULT 'ESCOLA',
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `id_ure` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_ue`),
  UNIQUE KEY `uk_ue_cie` (`cie`),
  KEY `fk_ue_ure` (`id_ure`),
  CONSTRAINT `fk_ue_ure` FOREIGN KEY (`id_ure`) REFERENCES `unidades_regionais` (`id_ure`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `unidades_escolares` (`id_ue`, `cie`, `ua`, `nome`, `modalidade`, `tipo_ue`, `endereco`, `numero`, `bairro`, `municipio`, `cidade`, `cep`, `telefone`, `email`, `id_ure`, `ativo`, `data_cadastro`) VALUES
(1, '012489', '41250', 'EE Cásper Líbero', 'Ensino Fundamental Anos Finais e Médio', 'ESCOLA', 'Rua Alziro de Oliveira', '120', 'Jardim Cerejeiras', 'Atibaia', 'Atibaia', '12951-240', '(11) 4412-3040', 'ee.casperlibero@educacao.sp.gov.br', 1, 1, '2026-09-24 09:00:00'),
(2, '012502', '41255', 'EE Professor José Fernando Paschoal', 'Ensino Fundamental e Médio', 'ESCOLA', 'Rua Maestro Danzi', '55', 'Vila Municipal', 'Bragança Paulista', 'Bragança Paulista', '12908-120', '(11) 4033-8822', 'ee.josefernando@educacao.sp.gov.br', 1, 1, '2026-09-24 09:00:00'),
(3, '012514', '41260', 'EE Silvio de Almeida', 'Ensino Médio Integral', 'ESCOLA', 'Rua Coronel Leme', '420', 'Centro', 'Bragança Paulista', 'Bragança Paulista', '12900-220', '(11) 4034-0199', 'ee.silvioalmeida@educacao.sp.gov.br', 1, 1, '2026-09-24 09:00:00'),
(4, '012526', '41265', 'EE Major Juvenal Alvim', 'Ensino Fundamental Anos Iniciais e Finais', 'ESCOLA', 'Praça Guilherme Gonçalves', '100', 'Centro', 'Atibaia', 'Atibaia', '12940-020', '(11) 4411-2010', 'ee.juvenalalvim@educacao.sp.gov.br', 1, 1, '2026-09-24 09:00:00');

CREATE TABLE IF NOT EXISTS `empresas` (
  `id_empresa` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `cnpj` varchar(20) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `numero_contrato` varchar(50) DEFAULT NULL,
  `data_inicio_contrato` date DEFAULT NULL,
  `data_fim_contrato` date DEFAULT NULL,
  `contrato_arquivo` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_empresa`),
  UNIQUE KEY `uk_empresa_cnpj` (`cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `empresas` (`id_empresa`, `nome`, `cnpj`, `endereco`, `numero`, `bairro`, `municipio`, `cep`, `telefone`, `email`, `numero_contrato`, `data_inicio_contrato`, `data_fim_contrato`, `contrato_arquivo`, `ativo`, `data_cadastro`) VALUES
(1, 'Plena Serviços de Apoio Escolar Ltda.', '70.430.408/0001-89', 'Rua Coronel Teófilo Leme', '845', 'Centro', 'Bragança Paulista', '12900-005', '(11) 4034-2187', 'contato@plenaservicos.com.br', 'CTR-014/2026', '2026-01-15', '2026-12-31', 'uploads/contratos/contrato_plena_servicos_ctr014_2026.pdf', 1, '2026-09-24 09:00:00');

CREATE TABLE IF NOT EXISTS `empresa_ure` (
  `id_empresa` int(11) NOT NULL,
  `id_ure` int(11) NOT NULL,
  PRIMARY KEY (`id_empresa`,`id_ure`),
  KEY `fk_empresa_ure_ure` (`id_ure`),
  CONSTRAINT `fk_empresa_ure_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON DELETE CASCADE,
  CONSTRAINT `fk_empresa_ure_ure` FOREIGN KEY (`id_ure`) REFERENCES `unidades_regionais` (`id_ure`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `empresa_ure` (`id_empresa`, `id_ure`) VALUES
(1, 1);

CREATE TABLE IF NOT EXISTS `usuarios_ure` (
  `id_usuario_ure` int(11) NOT NULL AUTO_INCREMENT,
  `id_ure` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `cpf` char(11) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `setor` enum('ASURE','SEFISC','EDU_ESPECIAL','GABINETE') NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `nivel_acesso` int(11) DEFAULT 1,
  `email` varchar(150) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario_ure`),
  UNIQUE KEY `uk_ure_user_cpf` (`cpf`),
  KEY `fk_usuario_ure_ure` (`id_ure`),
  CONSTRAINT `fk_usuario_ure_ure` FOREIGN KEY (`id_ure`) REFERENCES `unidades_regionais` (`id_ure`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios_ure` (`id_usuario_ure`, `id_ure`, `nome`, `cpf`, `senha`, `setor`, `cargo`, `nivel_acesso`, `email`, `ativo`, `data_cadastro`) VALUES
(1, 1, 'Eduardo Pinheiro de Moraes', '38897933980', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'GABINETE', 'Dirigente Regional de Ensino', 3, 'eduardo.moraes@educacao.sp.gov.br', 1, '2026-09-24 09:00:00'),
(2, 1, 'Beatriz Vasconcelos Lima', '08905621058', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'ASURE', 'Assistente Técnico de URE', 2, 'beatriz.vasconcelos@educacao.sp.gov.br', 1, '2026-09-24 09:00:00'),
(3, 1, 'Rodrigo Mendes Cavalcante', '16973929460', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'SEFISC', 'Supervisor de Fiscalização de Contratos', 2, 'rodrigo.cavalcante@educacao.sp.gov.br', 1, '2026-09-24 09:00:00'),
(4, 1, 'Luciana Cristina Campos', '34568254639', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'SEFISC', 'Oficial Administrativo', 1, 'luciana.campos@educacao.sp.gov.br', 1, '2026-09-24 09:00:00'),
(6, 1, 'André Luiz Barbosa', '68017529520', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'EDU_ESPECIAL', 'Auxiliar Administrativo', 1, 'andre.barbosa@educacao.sp.gov.br', 1, '2026-09-24 09:00:00');

CREATE TABLE IF NOT EXISTS `usuarios_supervisor` (
  `id_usuario_supervisor` int(11) NOT NULL AUTO_INCREMENT,
  `id_empresa` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `cpf` char(11) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario_supervisor`),
  UNIQUE KEY `uk_sup_cpf` (`cpf`),
  KEY `fk_supervisor_empresa` (`id_empresa`),
  CONSTRAINT `fk_supervisor_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios_supervisor` (`id_usuario_supervisor`, `id_empresa`, `nome`, `cpf`, `senha`, `email`, `telefone`, `ativo`, `data_cadastro`) VALUES
(1, 1, 'Marcelo Antunes Ribeiro', '57967958802', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'marcelo.ribeiro@plenaservicos.com.br', '(11) 97824-1590', 1, '2026-09-24 09:00:00');

CREATE TABLE IF NOT EXISTS `usuarios_pae` (
  `id_pae` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `cpf` char(11) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `contrato_arquivo` varchar(255) DEFAULT NULL,
  `id_empresa` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pae`),
  UNIQUE KEY `uk_pae_cpf` (`cpf`),
  KEY `fk_pae_empresa` (`id_empresa`),
  CONSTRAINT `fk_pae_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios_pae` (`id_pae`, `nome`, `cpf`, `senha`, `email`, `telefone`, `contrato_arquivo`, `id_empresa`, `ativo`, `data_cadastro`) VALUES
(1, 'Aline Cristina Silveira', '92105689078', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'aline.silveira@plenaservicos.com.br', '(11) 98642-1098', NULL, 1, 1, '2026-09-24 09:00:00'),
(2, 'Rafael Henrique Duarte', '89839629174', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'rafael.duarte@plenaservicos.com.br', '(11) 97519-3420', NULL, 1, 1, '2026-09-24 09:00:00'),
(4, 'Simone Aparecida Cunha', '51294837261', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'simone.cunha@plenaservicos.com.br', '(11) 99182-7364', NULL, 1, 1, '2026-09-24 09:00:00'),
(7, 'Bruno César Guimarães', '64918273019', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'bruno.guimaraes@plenaservicos.com.br', '(11) 98122-4455', NULL, 1, 1, '2026-03-10 09:00:00'),
(8, 'Camila Rocha Nogueira', '71928304918', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'camila.rocha@plenaservicos.com.br', '(11) 97233-1122', NULL, 1, 1, '2026-03-10 09:00:00'),
(9, 'Marcos Paulo Farias', '82930419283', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'marcos.farias@plenaservicos.com.br', '(11) 99344-8877', NULL, 1, 1, '2026-03-10 09:00:00');

CREATE TABLE IF NOT EXISTS `usuarios_ue` (
  `id_usuario_ue` int(11) NOT NULL AUTO_INCREMENT,
  `id_ue` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `cpf` char(11) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `cargo` enum('DIRETOR','VICE_DIRETOR','COORDENADOR','SECRETARIO','GOE') NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario_ue`),
  UNIQUE KEY `uk_ue_user_cpf` (`cpf`),
  KEY `fk_usuario_ue` (`id_ue`),
  CONSTRAINT `fk_usuario_ue` FOREIGN KEY (`id_ue`) REFERENCES `unidades_escolares` (`id_ue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios_ue` (`id_usuario_ue`, `id_ue`, `nome`, `cpf`, `senha`, `cargo`, `email`, `telefone`, `ativo`, `data_cadastro`) VALUES
(1, 1, 'Patrícia Helena Prado', '85912099156', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'DIRETOR', 'patricia.prado@educacao.sp.gov.br', '(11) 98765-4321', 1, '2026-09-24 09:00:00'),
(2, 2, 'Regina Célia Alcantara', '01630953458', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'VICE_DIRETOR', 'regina.alcantara@educacao.sp.gov.br', '(11) 97654-3210', 1, '2026-09-24 09:00:00'),
(3, 3, 'Clarisse Bueno de Camargo', '31294857201', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'COORDENADOR', 'clarisse.camargo@educacao.sp.gov.br', '(11) 96543-2109', 1, '2026-09-24 09:00:00'),
(4, 4, 'Jorge Luiz Antunes', '49182736450', '$2y$10$8vWfqgj1YXUvjF/XlHacbe/.jqlZljtgL2gMlgeamIcFiwUSl5TfS', 'SECRETARIO', 'jorge.antunes@educacao.sp.gov.br', '(11) 95432-1098', 1, '2026-09-24 09:00:00');

CREATE TABLE IF NOT EXISTS `alunos` (
  `id_aluno` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `cpf` char(11) DEFAULT NULL,
  `ra` varchar(30) DEFAULT NULL,
  `turno_aula` enum('MANHÃ','TARDE','NOITE','INTEGRAL') DEFAULT NULL,
  `genero` varchar(50) DEFAULT NULL,
  `raca` varchar(50) DEFAULT NULL,
  `municipio_nascimento` varchar(100) DEFAULT NULL,
  `serie` varchar(50) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `descricao_deficiencia` text DEFAULT NULL,
  `descricao_cuidados` text DEFAULT NULL,
  `nome_responsavel` varchar(150) DEFAULT NULL,
  `cpf_responsavel` char(11) DEFAULT NULL,
  `foto_arquivo` varchar(255) DEFAULT NULL,
  `termo_responsabilidade_arquivo` varchar(255) DEFAULT NULL,
  `status_aprovacao` enum('PENDENTE','APROVADO','REPROVADO','PENDENTE_CORRECAO','ARQUIVADO') DEFAULT 'PENDENTE',
  `motivo_reprovacao` text DEFAULT NULL,
  `motivo_arquivamento` text DEFAULT NULL,
  `data_arquivamento` datetime DEFAULT NULL,
  `arquivado_por_nome` varchar(150) DEFAULT NULL,
  `arquivado_por_cpf` char(11) DEFAULT NULL,
  `arquivado_por_perfil` varchar(50) DEFAULT NULL,
  `deliberado_por_nome` varchar(150) DEFAULT NULL,
  `deliberado_por_cpf` char(11) DEFAULT NULL,
  `data_deliberacao` datetime DEFAULT NULL,
  `id_ue` int(11) DEFAULT NULL,
  `id_usuario_ue` int(11) DEFAULT NULL,
  `cadastrado_por_nome` varchar(150) DEFAULT NULL,
  `cadastrado_por_cpf` varchar(20) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_aluno`),
  KEY `fk_aluno_ue` (`id_ue`),
  KEY `fk_aluno_usuario_ue` (`id_usuario_ue`),
  CONSTRAINT `fk_aluno_ue` FOREIGN KEY (`id_ue`) REFERENCES `unidades_escolares` (`id_ue`) ON UPDATE CASCADE,
  CONSTRAINT `fk_aluno_usuario_ue` FOREIGN KEY (`id_usuario_ue`) REFERENCES `usuarios_ue` (`id_usuario_ue`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `alunos` (`id_aluno`, `nome`, `cpf`, `ra`, `turno_aula`, `genero`, `raca`, `municipio_nascimento`, `serie`, `data_nascimento`, `descricao_deficiencia`, `descricao_cuidados`, `nome_responsavel`, `cpf_responsavel`, `foto_arquivo`, `termo_responsabilidade_arquivo`, `status_aprovacao`, `motivo_reprovacao`, `motivo_arquivamento`, `data_arquivamento`, `arquivado_por_nome`, `arquivado_por_cpf`, `arquivado_por_perfil`, `deliberado_por_nome`, `deliberado_por_cpf`, `data_deliberacao`, `id_ue`, `id_usuario_ue`, `cadastrado_por_nome`, `cadastrado_por_cpf`, `data_cadastro`) VALUES
(1, 'Enzo Gabriel de Almeida', '06403706160', '112345678-SP', 'MANHÃ', 'MASCULINO', 'BRANCA', 'Atibaia', '6º ANO', '2014-04-12', 'Transtorno do Espectro Autista (TEA - CID F84.0)', 'Necessita de mediação pedagógica contínua, suporte em momentos de sobrecarga sensorial e apoio na organização da rotina e transição de aulas.', 'Renata Cristina de Almeida', '10998616249', 'uploads/fotos/20260928141943_7be9c17807c9.jpg', 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-09-24 15:30:00', 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-09-24 09:00:00'),
(2, 'Sophia Helena Martins', '77184557000', '113987654-SP', 'TARDE', 'FEMININO', 'PARDA', 'Bragança Paulista', '7º ANO', '2013-08-25', 'Paralisia Cerebral Espástica com diplegia motora (CID G80.1)', 'Usuária de cadeira de rodas; necessita de auxílio para locomoção pelo prédio escolar, uso de sanitários e transferências posturais.', 'Cláudia Martins da Silva', '19793723491', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-09-24 15:30:00', 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-09-24 09:00:00'),
(3, 'Matheus Henrique Ramos', '74826774613', '114561239-SP', 'MANHÃ', 'MASCULINO', 'BRANCA', 'Bragança Paulista', '8º ANO', '2012-11-03', 'Deficiência Intelectual Moderada associada a TDAH (CID F71 / F90)', 'Requer mediação direcionada na realização de atividades pedagógicas, supervisão durante intervalos e suporte nas relações sociais interpessoais.', 'Marcos Vinícius Ramos', '63942918544', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-09-24 15:30:00', 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-09-24 09:00:00'),
(4, 'Laura Beatriz Santos', '62852031647', '115890234-SP', 'TARDE', 'FEMININO', 'PRETA', 'Atibaia', '6º ANO', '2014-02-18', 'Mielomeningocele com paraparesia e hidrocefalia corrigida (CID Q05)', 'Locomoção assistida com uso de andador infantil, necessidade de auxílio na higienização periódica e verificação de postura em sala.', 'Juliana dos Santos Ferraz', '97691385412', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '', '2026-10-02 09:44:51', 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-09-25 10:30:00'),
(5, 'Cauã Felipe Nogueira', '53918274601', '116748291-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Bragança Paulista', '9º ANO', '2011-06-14', 'Síndrome de Down com atraso motor e fonoaudiológico (CID Q90.9)', 'Suporte em atividades com motricidade fina, apoio na alimentação e estímulo à autonomia para participação em projetos coletivos.', 'Patrícia Nogueira Lopes', '48291049281', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE_CORRECAO', 'Favor anexar relatório neurológico atualizado com carimbo e assinatura legíveis do médico especialista.', NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-09-26 14:15:00', 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-09-26 14:15:00'),
(8, 'Beatriz Yasmin Oliveira', '48291039481', '119049281-SP', 'MANHÃ', 'FEMININO', 'PARDA', 'Bragança Paulista', '3ª SÉRIE', '2008-03-30', 'Paralisia Cerebral com monoparesia braquial direita (CID G80)', 'Estudante concluiu o Ensino Médio com êxito e autonomia estabelecida.', 'Tereza Cristina Oliveira', '58192039481', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'Conclusão regular do Ensino Médio pelo estudante. Encerramento do ciclo de apoio escolar no SIGEI.', '2026-09-29 16:00:00', 'Eduardo Pinheiro de Moraes', '38897933980', 'DIRIGENTE', 'Camila Fernanda Moreira', '06974141246', '2026-09-24 15:30:00', 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-09-24 09:00:00'),
(11, 'Arthur Henrique Silveira', '40918237461', '122345110-SP', 'MANHÃ', 'MASCULINO', 'BRANCA', 'Atibaia', '7º ANO', '2013-05-14', 'Transtorno do Espectro Autista (TEA - CID F84.0)', 'Necessita de apoio na mediação pedagógica, contenção em crises sensoriais e acompanhamento no intervalo.', 'Juliana Silveira Ramos', '81920394857', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-04-12 14:00:00', 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-04-10 08:30:00'),
(12, 'Helena Beatriz Alcantara', '51029384756', '123456221-SP', 'TARDE', 'FEMININO', 'PARDA', 'Atibaia', '8º ANO', '2012-09-20', 'Paralisia Cerebral Espástica Diparética (CID G80.1)', 'Cadeira de rodas manual; suporte para transferências, uso do sanitário e transporte até salas de aula.', 'Carlos Eduardo Alcantara', '92039485716', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-04-18 10:30:00', 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-04-15 11:20:00'),
(13, 'Bernardo Souza Toledo', '62130495867', '124567332-SP', 'MANHÃ', 'MASCULINO', 'BRANCA', 'Bragança Paulista', '6º ANO', '2014-01-11', 'Síndrome de Down com atraso psicomotor (CID Q90.9)', 'Apoio na motricidade fina, organização do material escolar e estimulação à fala.', 'Mariana Souza Toledo', '03948571625', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-05-05 16:00:00', 2, NULL, 'Patrícia Helena Prado', '85912099156', '2026-05-02 09:15:00'),
(14, 'Valentina Mendes Rocha', '73241506978', '125678443-SP', 'TARDE', 'FEMININO', 'PRETA', 'Bragança Paulista', '9º ANO', '2011-08-30', 'Deficiência Intelectual Moderada associada a Epilepsia (CID F71 / G40)', 'Supervisão constante em locais abertos e suporte pedagógico individualizado.', 'Renata Mendes Rocha', '14059682736', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-05-20 11:00:00', 2, NULL, 'Patrícia Helena Prado', '85912099156', '2026-05-18 14:40:00'),
(15, 'Davi Lucca Farias', '84352617089', '126789554-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Bragança Paulista', '1ª SÉRIE', '2010-03-25', 'Transtorno do Espectro Autista Severo Não Verbal (CID F84.0)', 'Necessidade de cuidador dedicado para alimentação, higienização e comunicação alternativa.', 'Fabiana Farias Lima', '25160793847', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-06-08 09:30:00', 3, NULL, 'Patrícia Helena Prado', '85912099156', '2026-06-04 10:00:00'),
(16, 'Larissa Fernanda Duarte', '95463728190', '127890665-SP', 'INTEGRAL', 'FEMININO', 'BRANCA', 'Atibaia', '2ª SÉRIE', '2009-11-12', 'Mielomeningocele com Bexiga Neurogênica (CID Q05.9)', 'Uso de cadeira de rodas; necessidade de sondagem vesical e auxílio integral na locomoção escolar.', 'Sandra Mara Duarte', '36271804958', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-06-22 15:45:00', 4, NULL, 'Patrícia Helena Prado', '85912099156', '2026-06-19 13:20:00'),
(17, 'Thiago Emanuel Rezende', '06574839201', '128901776-SP', 'MANHÃ', 'MASCULINO', 'BRANCA', 'Atibaia', '6º ANO', '2014-07-08', 'Déficit de atenção com desatenção pontual em sala', 'Solicitação enviada pela escola sem comprovação de dependência funcional.', 'Cláudio Rezende', '47382915069', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'REPROVADO', 'Estudante não apresenta dependência em atividades de vida diária ou locomoção. Recomendado encaminhamento para Sala de Recursos (AEE).', NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-07-02 11:00:00', 4, NULL, 'Patrícia Helena Prado', '85912099156', '2026-06-28 09:00:00'),
(18, 'Alice Vitória Pinheiro', '17685940312', '129012887-SP', 'TARDE', 'FEMININO', 'PARDA', 'Bragança Paulista', '7º ANO', '2013-10-17', 'Transtorno do Espectro Autista (TEA - CID F84.0)', 'Laudo antigo anexado. Necessita de reavaliação médica circunstanciada.', 'Vanessa Pinheiro', '58493026170', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE_CORRECAO', 'Anexar laudo com data de emissão inferior a 12 meses e parecer pedagógico inicial da escola.', NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '06974141246', '2026-07-15 14:30:00', 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-07-12 10:15:00'),
(19, 'Gustavo Henrique Vasconcelos', '28796051423', '130123998-SP', 'MANHÃ', 'MASCULINO', 'BRANCA', 'Bragança Paulista', '8º ANO', '2012-04-03', 'Deficiência Visual Severa (CID H54.1)', 'Necessita de leitor de tela, guia em escadarias e ampliação de provas.', 'Rogério Vasconcelos', '69504137281', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'André Luiz Barbosa', '', '2026-10-06 09:52:11', 2, NULL, 'Patrícia Helena Prado', '85912099156', '2026-09-30 08:45:00'),
(20, 'Manuela Cristina Barros', '39807162534', '131234009-SP', 'TARDE', 'FEMININO', 'BRANCA', 'Bragança Paulista', '9º ANO', '2011-12-19', 'Transtorno do Espectro Autista com hipersensibilidade auditiva (CID F84.0)', 'Suporte em transições de sala e auxílio na comunicação.', 'Luciana Barros', '70615248392', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Camila Fernanda Moreira', '', '2026-10-06 10:40:23', 3, NULL, 'Patrícia Helena Prado', '85912099156', '2026-10-01 11:30:00'),
(42, 'Gabriel Henrique - UE 1', '10000000101', '90000101-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Atibaia', '6º ANO', '2015-10-02', 'Transtorno do Espectro Autista (TEA - CID F84.0)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 1-1', '20000000101', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-10-02 10:12:36'),
(43, 'Gabriel Henrique - UE 2', '10000000201', '90000201-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Bragança Paulista', '6º ANO', '2015-10-02', 'Transtorno do Espectro Autista (TEA - CID F84.0)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 2-1', '20000000201', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'André Luiz Barbosa', '', '2026-10-07 08:23:51', 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-10-02 10:12:36'),
(44, 'Gabriel Henrique - UE 3', '10000000301', '90000301-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Bragança Paulista', '6º ANO', '2015-10-02', 'Transtorno do Espectro Autista (TEA - CID F84.0)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 3-1', '20000000301', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-10-02 10:12:36'),
(45, 'Gabriel Henrique - UE 4', '10000000401', '90000401-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Atibaia', '6º ANO', '2015-10-02', 'Transtorno do Espectro Autista (TEA - CID F84.0)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 4-1', '20000000401', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'hlhl', '2026-10-07 07:23:13', 'Jorge Luiz Antunes', '49182736450', 'USUARIO_ESCOLA', NULL, NULL, NULL, 4, NULL, 'Jorge Luiz Antunes', '49182736450', '2026-10-02 10:12:36'),
(52, 'Maria Eduarda - UE 1', '10000000102', '90000102-SP', 'TARDE', 'FEMININO', 'PRETA', 'Atibaia', '7º ANO', '2014-10-02', 'Paralisia Cerebral (CID G80.1)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 1-2', '20000000102', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'hhgyt', '2026-10-06 10:40:46', 'Rodrigo Mendes Cavalcante', '16973929460', 'USUARIO_SEFISC', NULL, NULL, NULL, 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-10-02 10:12:36'),
(53, 'Maria Eduarda - UE 2', '10000000202', '90000202-SP', 'TARDE', 'FEMININO', 'PRETA', 'Bragança Paulista', '7º ANO', '2014-10-02', 'Paralisia Cerebral (CID G80.1)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 2-2', '20000000202', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-10-02 10:12:36'),
(54, 'Maria Eduarda - UE 3', '10000000302', '90000302-SP', 'TARDE', 'FEMININO', 'PRETA', 'Bragança Paulista', '7º ANO', '2014-10-02', 'Paralisia Cerebral (CID G80.1)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 3-2', '20000000302', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-10-02 10:12:36'),
(55, 'Maria Eduarda - UE 4', '10000000402', '90000402-SP', 'TARDE', 'FEMININO', 'PRETA', 'Atibaia', '7º ANO', '2014-10-02', 'Paralisia Cerebral (CID G80.1)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 4-2', '20000000402', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, 'Jorge Luiz Antunes', '49182736450', '2026-10-02 10:12:36'),
(62, 'Lucas Miguel - UE 1', '10000000103', '90000103-SP', 'INTEGRAL', 'MASCULINO', 'AMARELA', 'Atibaia', '8º ANO', '2013-10-02', 'Síndrome de Down (CID Q90.9)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 1-3', '20000000103', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Administrador do Sistema', '83918234150', '2026-10-02 10:12:36', 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-10-02 10:12:36'),
(63, 'Lucas Miguel - UE 2', '10000000203', '90000203-SP', 'INTEGRAL', 'MASCULINO', 'AMARELA', 'Bragança Paulista', '8º ANO', '2013-10-02', 'Síndrome de Down (CID Q90.9)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 2-3', '20000000203', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Administrador do Sistema', '83918234150', '2026-10-02 10:12:36', 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-10-02 10:12:36'),
(64, 'Lucas Miguel - UE 3', '10000000303', '90000303-SP', 'INTEGRAL', 'MASCULINO', 'AMARELA', 'Bragança Paulista', '8º ANO', '2013-10-02', 'Síndrome de Down (CID Q90.9)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 3-3', '20000000303', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Administrador do Sistema', '83918234150', '2026-10-02 10:12:36', 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-10-02 10:12:36'),
(65, 'Lucas Miguel - UE 4', '10000000403', '90000403-SP', 'INTEGRAL', 'MASCULINO', 'AMARELA', 'Atibaia', '8º ANO', '2013-10-02', 'Síndrome de Down (CID Q90.9)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 4-3', '20000000403', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Administrador do Sistema', '83918234150', '2026-10-02 10:12:36', 4, NULL, 'Jorge Luiz Antunes', '49182736450', '2026-10-02 10:12:36'),
(72, 'Ana Carolina - UE 1', '10000000104', '90000104-SP', 'NOITE', 'FEMININO', 'BRANCA', 'Atibaia', '9º ANO', '2012-10-02', 'Deficiência Intelectual (CID F71)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 1-4', '20000000104', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'k,gl', '2026-10-07 07:21:50', 'Rodrigo Mendes Cavalcante', '16973929460', 'USUARIO_SEFISC', 'Administrador do Sistema', '83918234150', '2026-10-02 10:12:36', 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-10-02 10:12:36'),
(73, 'Ana Carolina - UE 2', '10000000204', '90000204-SP', 'NOITE', 'FEMININO', 'BRANCA', 'Bragança Paulista', '9º ANO', '2012-10-02', 'Deficiência Intelectual (CID F71)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 2-4', '20000000204', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Administrador do Sistema', '83918234150', '2026-10-02 10:12:36', 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-10-02 10:12:36'),
(74, 'Ana Carolina - UE 3', '10000000304', '90000304-SP', 'NOITE', 'FEMININO', 'BRANCA', 'Bragança Paulista', '9º ANO', '2012-10-02', 'Deficiência Intelectual (CID F71)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 3-4', '20000000304', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Administrador do Sistema', '83918234150', '2026-10-02 10:12:36', 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-10-02 10:12:36'),
(75, 'Ana Carolina - UE 4', '10000000404', '90000404-SP', 'NOITE', 'FEMININO', 'BRANCA', 'Atibaia', '9º ANO', '2012-10-02', 'Deficiência Intelectual (CID F71)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 4-4', '20000000404', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'APROVADO', NULL, NULL, NULL, NULL, NULL, NULL, 'Administrador do Sistema', '83918234150', '2026-10-02 10:12:36', 4, NULL, 'Jorge Luiz Antunes', '49182736450', '2026-10-02 10:12:36'),
(82, 'Miguel Gabriel - UE 1', '10000000105', '90000105-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Atibaia', '1ª SÉRIE', '2011-10-02', 'Deficiência Visual (CID H54.2)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 1-5', '20000000105', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'REPROVADO', 'Documentação apresentada não atende aos critérios necessários para aprovação.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-10-02 10:12:36'),
(83, 'Miguel Gabriel - UE 2', '10000000205', '90000205-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Bragança Paulista', '1ª SÉRIE', '2011-10-02', 'Deficiência Visual (CID H54.2)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 2-5', '20000000205', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'REPROVADO', 'Documentação apresentada não atende aos critérios necessários para aprovação.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-10-02 10:12:36'),
(84, 'Miguel Gabriel - UE 3', '10000000305', '90000305-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Bragança Paulista', '1ª SÉRIE', '2011-10-02', 'Deficiência Visual (CID H54.2)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 3-5', '20000000305', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'REPROVADO', 'Documentação apresentada não atende aos critérios necessários para aprovação.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-10-02 10:12:36'),
(85, 'Miguel Gabriel - UE 4', '10000000405', '90000405-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Atibaia', '1ª SÉRIE', '2011-10-02', 'Deficiência Visual (CID H54.2)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 4-5', '20000000405', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'REPROVADO', 'Documentação apresentada não atende aos critérios necessários para aprovação.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, 'Jorge Luiz Antunes', '49182736450', '2026-10-02 10:12:36'),
(92, 'Julia Fernanda - UE 1', '10000000106', '90000106-SP', 'TARDE', 'FEMININO', 'PRETA', 'Atibaia', '2ª SÉRIE', '2010-10-02', 'Transtorno do Espectro Autista (TEA - CID F84.0)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 1-6', '20000000106', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'REPROVADO', 'Documentação apresentada não atende aos critérios necessários para aprovação.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-10-02 10:12:36'),
(93, 'Julia Fernanda - UE 2', '10000000206', '90000206-SP', 'TARDE', 'FEMININO', 'PRETA', 'Bragança Paulista', '2ª SÉRIE', '2010-10-02', 'Transtorno do Espectro Autista (TEA - CID F84.0)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 2-6', '20000000206', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'REPROVADO', 'Documentação apresentada não atende aos critérios necessários para aprovação.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-10-02 10:12:36'),
(94, 'Julia Fernanda - UE 3', '10000000306', '90000306-SP', 'TARDE', 'FEMININO', 'PRETA', 'Bragança Paulista', '2ª SÉRIE', '2010-10-02', 'Transtorno do Espectro Autista (TEA - CID F84.0)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 3-6', '20000000306', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'REPROVADO', 'Documentação apresentada não atende aos critérios necessários para aprovação.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-10-02 10:12:36'),
(95, 'Julia Fernanda - UE 4', '10000000406', '90000406-SP', 'TARDE', 'FEMININO', 'PRETA', 'Atibaia', '2ª SÉRIE', '2010-10-02', 'Transtorno do Espectro Autista (TEA - CID F84.0)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 4-6', '20000000406', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'REPROVADO', 'Documentação apresentada não atende aos critérios necessários para aprovação.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, 'Jorge Luiz Antunes', '49182736450', '2026-10-02 10:12:36'),
(102, 'Arthur Oliveira - UE 1', '10000000107', '90000107-SP', 'INTEGRAL', 'MASCULINO', 'AMARELA', 'Atibaia', '3ª SÉRIE', '2009-10-02', 'Paralisia Cerebral (CID G80.1)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 1-7', '20000000107', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE_CORRECAO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-10-02 10:12:36'),
(103, 'Arthur Oliveira - UE 2', '10000000207', '90000207-SP', 'INTEGRAL', 'MASCULINO', 'AMARELA', 'Bragança Paulista', '3ª SÉRIE', '2009-10-02', 'Paralisia Cerebral (CID G80.1)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 2-7', '20000000207', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE_CORRECAO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-10-02 10:12:36'),
(104, 'Arthur Oliveira - UE 3', '10000000307', '90000307-SP', 'INTEGRAL', 'MASCULINO', 'AMARELA', 'Bragança Paulista', '3ª SÉRIE', '2009-10-02', 'Paralisia Cerebral (CID G80.1)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 3-7', '20000000307', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE_CORRECAO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-10-02 10:12:36'),
(105, 'Arthur Oliveira - UE 4', '10000000407', '90000407-SP', 'INTEGRAL', 'MASCULINO', 'AMARELA', 'Atibaia', '3ª SÉRIE', '2009-10-02', 'Paralisia Cerebral (CID G80.1)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 4-7', '20000000407', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE_CORRECAO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, 'Jorge Luiz Antunes', '49182736450', '2026-10-02 10:12:36'),
(112, 'Beatriz Santos - UE 1', '10000000108', '90000108-SP', 'NOITE', 'FEMININO', 'BRANCA', 'Atibaia', '6º ANO', '2008-10-02', 'Síndrome de Down (CID Q90.9)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 1-8', '20000000108', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE_CORRECAO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-10-02 10:12:36'),
(113, 'Beatriz Santos - UE 2', '10000000208', '90000208-SP', 'NOITE', 'FEMININO', 'BRANCA', 'Bragança Paulista', '6º ANO', '2008-10-02', 'Síndrome de Down (CID Q90.9)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 2-8', '20000000208', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE_CORRECAO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-10-02 10:12:36'),
(114, 'Beatriz Santos - UE 3', '10000000308', '90000308-SP', 'NOITE', 'FEMININO', 'BRANCA', 'Bragança Paulista', '6º ANO', '2008-10-02', 'Síndrome de Down (CID Q90.9)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 3-8', '20000000308', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE_CORRECAO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-10-02 10:12:36'),
(115, 'Beatriz Santos - UE 4', '10000000408', '90000408-SP', 'NOITE', 'FEMININO', 'BRANCA', 'Atibaia', '6º ANO', '2008-10-02', 'Síndrome de Down (CID Q90.9)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 4-8', '20000000408', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'PENDENTE_CORRECAO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, 'Jorge Luiz Antunes', '49182736450', '2026-10-02 10:12:36'),
(122, 'Rafael Augusto - UE 1', '10000000109', '90000109-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Atibaia', '7º ANO', '2007-10-02', 'Deficiência Intelectual (CID F71)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 1-9', '20000000109', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'Processo encerrado para fins de teste do sistema.', '2026-10-02 10:12:36', 'Administrador do Sistema', '83918234150', 'ADMIN', NULL, NULL, NULL, 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-10-02 10:12:36'),
(123, 'Rafael Augusto - UE 2', '10000000209', '90000209-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Bragança Paulista', '7º ANO', '2007-10-02', 'Deficiência Intelectual (CID F71)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 2-9', '20000000209', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'Processo encerrado para fins de teste do sistema.', '2026-10-02 10:12:36', 'Administrador do Sistema', '83918234150', 'ADMIN', NULL, NULL, NULL, 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-10-02 10:12:36'),
(124, 'Rafael Augusto - UE 3', '10000000309', '90000309-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Bragança Paulista', '7º ANO', '2007-10-02', 'Deficiência Intelectual (CID F71)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 3-9', '20000000309', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'Processo encerrado para fins de teste do sistema.', '2026-10-02 10:12:36', 'Administrador do Sistema', '83918234150', 'ADMIN', NULL, NULL, NULL, 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-10-02 10:12:36'),
(125, 'Rafael Augusto - UE 4', '10000000409', '90000409-SP', 'MANHÃ', 'MASCULINO', 'PARDA', 'Atibaia', '7º ANO', '2007-10-02', 'Deficiência Intelectual (CID F71)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 4-9', '20000000409', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'Processo encerrado para fins de teste do sistema.', '2026-10-02 10:12:36', 'Administrador do Sistema', '83918234150', 'ADMIN', NULL, NULL, NULL, 4, NULL, 'Jorge Luiz Antunes', '49182736450', '2026-10-02 10:12:36'),
(132, 'Laura Beatriz - UE 1', '10000000110', '90000110-SP', 'TARDE', 'FEMININO', 'PRETA', 'Atibaia', '8º ANO', '2006-10-02', 'Deficiência Visual (CID H54.2)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 1-10', '20000000110', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'Processo encerrado para fins de teste do sistema.', '2026-10-02 10:12:36', 'Administrador do Sistema', '83918234150', 'ADMIN', NULL, NULL, NULL, 1, NULL, 'Patrícia Helena Prado', '85912099156', '2026-10-02 10:12:36'),
(133, 'Laura Beatriz - UE 2', '10000000210', '90000210-SP', 'TARDE', 'FEMININO', 'PRETA', 'Bragança Paulista', '8º ANO', '2006-10-02', 'Deficiência Visual (CID H54.2)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 2-10', '20000000210', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'Processo encerrado para fins de teste do sistema.', '2026-10-02 10:12:36', 'Administrador do Sistema', '83918234150', 'ADMIN', NULL, NULL, NULL, 2, NULL, 'Regina Célia Alcantara', '01630953458', '2026-10-02 10:12:36'),
(134, 'Laura Beatriz - UE 3', '10000000310', '90000310-SP', 'TARDE', 'FEMININO', 'PRETA', 'Bragança Paulista', '8º ANO', '2006-10-02', 'Deficiência Visual (CID H54.2)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 3-10', '20000000310', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'Processo encerrado para fins de teste do sistema.', '2026-10-02 10:12:36', 'Administrador do Sistema', '83918234150', 'ADMIN', NULL, NULL, NULL, 3, NULL, 'Clarisse Bueno de Camargo', '31294857201', '2026-10-02 10:12:36'),
(135, 'Laura Beatriz - UE 4', '10000000410', '90000410-SP', 'TARDE', 'FEMININO', 'PRETA', 'Atibaia', '8º ANO', '2006-10-02', 'Deficiência Visual (CID H54.2)', 'Necessita de acompanhamento e suporte pedagógico conforme as necessidades apresentadas pelo estudante.', 'Responsável do Aluno 4-10', '20000000410', NULL, 'uploads/documentos/20260928141943_6ff2423d9338.pdf', 'ARQUIVADO', NULL, 'Processo encerrado para fins de teste do sistema.', '2026-10-02 10:12:36', 'Administrador do Sistema', '83918234150', 'ADMIN', NULL, NULL, NULL, 4, NULL, 'Jorge Luiz Antunes', '49182736450', '2026-10-02 10:12:36');

CREATE TABLE IF NOT EXISTS `associacoes` (
  `id_associacao` int(11) NOT NULL AUTO_INCREMENT,
  `id_aluno` int(11) NOT NULL,
  `id_pae` int(11) NOT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `observacoes` text DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_associacao`),
  KEY `fk_associacao_aluno` (`id_aluno`),
  KEY `fk_associacao_pae` (`id_pae`),
  CONSTRAINT `fk_associacao_aluno` FOREIGN KEY (`id_aluno`) REFERENCES `alunos` (`id_aluno`) ON DELETE CASCADE,
  CONSTRAINT `fk_associacao_pae` FOREIGN KEY (`id_pae`) REFERENCES `usuarios_pae` (`id_pae`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `associacoes` (`id_associacao`, `id_aluno`, `id_pae`, `data_inicio`, `data_fim`, `ativo`, `observacoes`, `criado_em`) VALUES
(1, 1, 1, 1, '2026-09-24', NULL),
(2, 2, 2, 1, '2026-09-24', NULL),
(3, 3, 2, 1, '2026-09-24', NULL),
(6, 4, 8, 1, '2026-05-01', NULL),
(7, 11, 4, 1, '2026-05-01', NULL),
(8, 12, 9, 1, '2026-05-01', NULL),
(9, 13, 7, 1, '2026-05-01', NULL),
(10, 14, 4, 1, '2026-05-01', NULL),
(11, 15, 7, 1, '2026-05-01', NULL),
(12, 16, 9, 1, '2026-05-01', NULL);

CREATE TABLE IF NOT EXISTS `laudos` (
  `id_laudo` int(11) NOT NULL AUTO_INCREMENT,
  `id_aluno` int(11) NOT NULL,
  `tipo_documento` varchar(100) DEFAULT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `arquivo` varchar(255) NOT NULL,
  `data_envio` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_laudo`),
  KEY `fk_laudo_aluno` (`id_aluno`),
  CONSTRAINT `fk_laudo_aluno` FOREIGN KEY (`id_aluno`) REFERENCES `alunos` (`id_aluno`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `laudos` (`id_laudo`, `id_aluno`, `tipo_documento`, `titulo`, `arquivo`, `data_envio`) VALUES
(1, 1, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Neuropediátrico TEA - 2026', 'Avaliação neurológica com CID F84.0 confirmando necessidade de mediador escolar.', '2026-09-24 09:10:00'),
(2, 1, 'uploads/laudos/20260928141943_05e0427c3da3.pdf', 'LAUDO', 'Avaliação Fonoaudiológica', 'Exame fonoaudiológico indicando necessidade de comunicação aumentativa.', '2026-09-24 09:12:00'),
(3, 1, 'uploads/laudos/20260928141943_8f042e61df3e.pdf', 'DOCUMENTO', 'Certidão de Nascimento do Estudante', 'Documento de identificação civil civil complementar.', '2026-09-24 09:15:00'),
(4, 2, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Médico Fisiátrico - Paralisia Cerebral', 'Relatório ortopédico e fisiátrico apontando dependência de cadeira de rodas.', '2026-09-24 09:30:00'),
(5, 3, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Neuropsicológico - DI', 'Avaliação de funções cognitivas e inteligência global.', '2026-09-24 09:40:00'),
(8, 11, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Médico Especializado', 'Avaliação médica com diagnóstico conclusivo e CID.', '2026-04-10 08:30:00'),
(9, 12, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Médico Especializado', 'Avaliação médica com diagnóstico conclusivo e CID.', '2026-04-15 11:20:00'),
(10, 13, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Médico Especializado', 'Avaliação médica com diagnóstico conclusivo e CID.', '2026-05-02 09:15:00'),
(11, 14, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Médico Especializado', 'Avaliação médica com diagnóstico conclusivo e CID.', '2026-05-18 14:40:00'),
(12, 15, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Médico Especializado', 'Avaliação médica com diagnóstico conclusivo e CID.', '2026-06-04 10:00:00'),
(13, 16, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Médico Especializado', 'Avaliação médica com diagnóstico conclusivo e CID.', '2026-06-19 13:20:00'),
(14, 17, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Médico Especializado', 'Avaliação médica com diagnóstico conclusivo e CID.', '2026-06-28 09:00:00'),
(15, 18, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Médico Especializado', 'Avaliação médica com diagnóstico conclusivo e CID.', '2026-07-12 10:15:00'),
(16, 19, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Médico Especializado', 'Avaliação médica com diagnóstico conclusivo e CID.', '2026-09-30 08:45:00'),
(17, 20, 'uploads/laudos/20260928141943_95f26ebfcb41.pdf', 'LAUDO', 'Laudo Médico Especializado', 'Avaliação médica com diagnóstico conclusivo e CID.', '2026-10-01 11:30:00');

CREATE TABLE IF NOT EXISTS `relatorios` (
  `id_relatorio` int(11) NOT NULL AUTO_INCREMENT,
  `id_associacao` int(11) NOT NULL,
  `mes_referencia` char(7) NOT NULL,
  `arquivo` varchar(255) DEFAULT NULL,
  `status` enum('PENDENTE','ENVIADO','APROVADO','REJEITADO') DEFAULT 'PENDENTE',
  `observacoes` text DEFAULT NULL,
  `data_envio` datetime DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_relatorio`),
  KEY `fk_relatorio_associacao` (`id_associacao`),
  CONSTRAINT `fk_relatorio_associacao` FOREIGN KEY (`id_associacao`) REFERENCES `associacoes` (`id_associacao`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `relatorios` (`id_relatorio`, `id_associacao`, `mes_referencia`, `arquivo`, `status`, `observacoes`, `data_envio`, `data_cadastro`) VALUES
(1, 1, 'DIARIO', '2026-09', 'O estudante participou com tranquilidade das atividades da manhã. Foi mediado na organização dos cadernos e na compreensão dos enunciados de Língua Portuguesa e História. Na hora do intervalo, lanchou adequadamente com auxílio e interagiu de forma positiva com os colegas de sala no pátio.', 'Relatório diário de rotina e acompanhamento escolar.', NULL, 'ENVIADO', '2026-09-25 12:15:00', '2026-09-25 12:15:00'),
(2, 2, 'DIARIO', '2026-09', 'Suporte motor realizado com sucesso em todas as transferências necessárias. A estudante foi acompanhada até a sala de informática e o laboratório de ciências. Participou ativamente das tarefas em grupo com boa postura na cadeira de rodas.', 'Relatório diário de suporte motor e acessibilidade.', NULL, 'ENVIADO', '2026-09-25 17:30:00', '2026-09-25 17:30:00'),
(3, 1, 'MENSAL', '2026-09', 'Evolução mensal extremamente satisfatória no mês de setembro. Observou-se redução significativa nos episódios de desorganização sensorial, maior autonomia na alimentação e avanço expressivo na socialização com os pares da turma.', 'Fechamento mensal de atendimento pedagógico inclusivo.', NULL, 'ENVIADO', '2026-09-30 16:45:00', '2026-09-30 16:45:00'),
(6, 6, 'DIARIO', '2026-09', 'O estudante participou ativamente das aulas com mediação pedagógica contínua. Alimentação e locomoção realizadas com segurança e bom engajamento.', 'Registro diário de rotina.', NULL, 'ENVIADO', '2026-09-20 12:00:00', '2026-09-20 12:00:00'),
(7, 6, 'MENSAL', '2026-09', 'Fechamento mensal de atendimento inclusivo. O estudante demonstrou expressivo ganho de autonomia nas atividades diárias e integração com o grupo escolar.', 'Relatório mensal de acompanhamento.', NULL, 'ENVIADO', '2026-09-30 17:00:00', '2026-09-30 17:00:00'),
(8, 7, 'DIARIO', '2026-09', 'O estudante participou ativamente das aulas com mediação pedagógica contínua. Alimentação e locomoção realizadas com segurança e bom engajamento.', 'Registro diário de rotina.', NULL, 'ENVIADO', '2026-09-20 12:00:00', '2026-09-20 12:00:00'),
(9, 7, 'MENSAL', '2026-09', 'Fechamento mensal de atendimento inclusivo. O estudante demonstrou expressivo ganho de autonomia nas atividades diárias e integração com o grupo escolar.', 'Relatório mensal de acompanhamento.', NULL, 'ENVIADO', '2026-09-30 17:00:00', '2026-09-30 17:00:00'),
(10, 8, 'DIARIO', '2026-09', 'O estudante participou ativamente das aulas com mediação pedagógica contínua. Alimentação e locomoção realizadas com segurança e bom engajamento.', 'Registro diário de rotina.', NULL, 'ENVIADO', '2026-09-20 12:00:00', '2026-09-20 12:00:00'),
(11, 8, 'MENSAL', '2026-09', 'Fechamento mensal de atendimento inclusivo. O estudante demonstrou expressivo ganho de autonomia nas atividades diárias e integração com o grupo escolar.', 'Relatório mensal de acompanhamento.', NULL, 'ENVIADO', '2026-09-30 17:00:00', '2026-09-30 17:00:00'),
(12, 9, 'DIARIO', '2026-09', 'O estudante participou ativamente das aulas com mediação pedagógica contínua. Alimentação e locomoção realizadas com segurança e bom engajamento.', 'Registro diário de rotina.', NULL, 'ENVIADO', '2026-09-20 12:00:00', '2026-09-20 12:00:00'),
(13, 9, 'MENSAL', '2026-09', 'Fechamento mensal de atendimento inclusivo. O estudante demonstrou expressivo ganho de autonomia nas atividades diárias e integração com o grupo escolar.', 'Relatório mensal de acompanhamento.', NULL, 'ENVIADO', '2026-09-30 17:00:00', '2026-09-30 17:00:00'),
(14, 10, 'DIARIO', '2026-09', 'O estudante participou ativamente das aulas com mediação pedagógica contínua. Alimentação e locomoção realizadas com segurança e bom engajamento.', 'Registro diário de rotina.', NULL, 'ENVIADO', '2026-09-20 12:00:00', '2026-09-20 12:00:00'),
(15, 10, 'MENSAL', '2026-09', 'Fechamento mensal de atendimento inclusivo. O estudante demonstrou expressivo ganho de autonomia nas atividades diárias e integração com o grupo escolar.', 'Relatório mensal de acompanhamento.', NULL, 'ENVIADO', '2026-09-30 17:00:00', '2026-09-30 17:00:00'),
(16, 11, 'DIARIO', '2026-09', 'O estudante participou ativamente das aulas com mediação pedagógica contínua. Alimentação e locomoção realizadas com segurança e bom engajamento.', 'Registro diário de rotina.', NULL, 'ENVIADO', '2026-09-20 12:00:00', '2026-09-20 12:00:00'),
(17, 11, 'MENSAL', '2026-09', 'Fechamento mensal de atendimento inclusivo. O estudante demonstrou expressivo ganho de autonomia nas atividades diárias e integração com o grupo escolar.', 'Relatório mensal de acompanhamento.', NULL, 'ENVIADO', '2026-09-30 17:00:00', '2026-09-30 17:00:00'),
(18, 12, 'DIARIO', '2026-09', 'O estudante participou ativamente das aulas com mediação pedagógica contínua. Alimentação e locomoção realizadas com segurança e bom engajamento.', 'Registro diário de rotina.', NULL, 'ENVIADO', '2026-09-20 12:00:00', '2026-09-20 12:00:00'),
(19, 12, 'MENSAL', '2026-09', 'Fechamento mensal de atendimento inclusivo. O estudante demonstrou expressivo ganho de autonomia nas atividades diárias e integração com o grupo escolar.', 'Relatório mensal de acompanhamento.', NULL, 'ENVIADO', '2026-09-30 17:00:00', '2026-09-30 17:00:00');

CREATE TABLE IF NOT EXISTS `auditoria` (
  `id_auditoria` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `usuario_nome` varchar(150) NOT NULL,
  `usuario_cpf` varchar(20) DEFAULT NULL,
  `usuario_perfil` varchar(50) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `acao` varchar(50) NOT NULL,
  `entidade` varchar(50) NOT NULL,
  `id_registro` int(11) DEFAULT NULL,
  `detalhes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `ip_origem` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_modulo_acao` (`modulo`,`acao`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_entidade_reg` (`entidade`,`id_registro`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `notificacoes_lidas` (
  `id_leitura` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `perfil` varchar(50) NOT NULL,
  `notificacao_key` varchar(100) NOT NULL,
  `lida_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_leitura`),
  UNIQUE KEY `uk_notif_lida` (`id_usuario`,`perfil`,`notificacao_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `recuperacao_senha` (
  `id_recuperacao` int(11) NOT NULL AUTO_INCREMENT,
  `cpf` char(11) NOT NULL,
  `tabela_usuario` varchar(50) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `criado_em` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_recuperacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `termos_aceite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `perfil` varchar(50) NOT NULL,
  `versao` varchar(20) NOT NULL,
  `data_aceite` datetime DEFAULT current_timestamp(),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;