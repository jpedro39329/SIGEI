-- ============================================================
-- SIGEI - MIGRAÇÃO DE BANCO: AUDITORIA E DESACOPLAMENTO HISTÓRICO
-- Execute este script no banco de dados do SIGEI
-- ============================================================

-- 1. Criação da tabela central de auditoria
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id_auditoria` INT AUTO_INCREMENT PRIMARY KEY,
  `id_usuario` INT NULL,
  `usuario_nome` VARCHAR(150) NOT NULL,
  `usuario_cpf` VARCHAR(20) NULL,
  `usuario_perfil` VARCHAR(50) NOT NULL,
  `modulo` VARCHAR(50) NOT NULL,
  `acao` VARCHAR(50) NOT NULL,
  `entidade` VARCHAR(50) NOT NULL,
  `id_registro` INT NULL,
  `detalhes` LONGTEXT NULL,
  `ip_origem` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_modulo_acao` (`modulo`, `acao`),
  INDEX `idx_usuario` (`id_usuario`),
  INDEX `idx_entidade_reg` (`entidade`, `id_registro`),
  INDEX `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Adicionar colunas de snapshot histórico de quem cadastrou o aluno (se ainda não existirem)
-- Nota: Caso execute via phpMyAdmin ou CLI MariaDB
SET @dbname = DATABASE();
SET @tablename = "alunos";
SET @colname = "cadastrado_por_nome";

-- Adiciona cadastrado_por_nome se não existir
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @colname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE alunos ADD COLUMN cadastrado_por_nome VARCHAR(150) NULL AFTER id_usuario_ue"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Adiciona cadastrado_por_cpf se não existir
SET @colname = "cadastrado_por_cpf";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @colname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE alunos ADD COLUMN cadastrado_por_cpf VARCHAR(20) NULL AFTER cadastrado_por_nome"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3. Preenchimento retroativo dos dados de quem cadastrou os alunos existentes
UPDATE alunos a
JOIN usuarios_ue uue ON a.id_usuario_ue = uue.id_usuario_ue
SET a.cadastrado_por_nome = uue.nome,
    a.cadastrado_por_cpf = uue.cpf
WHERE a.cadastrado_por_nome IS NULL OR a.cadastrado_por_nome = '';

-- 4. Modificar a Foreign Key fk_aluno_usuario_ue para ON DELETE SET NULL
-- Para que a exclusão/remoção do usuário da escola não quebre a integridade referencial nem apague o aluno.
ALTER TABLE alunos DROP FOREIGN KEY `fk_aluno_usuario_ue`;

ALTER TABLE alunos 
  MODIFY `id_usuario_ue` INT(11) NULL;

ALTER TABLE alunos 
  ADD CONSTRAINT `fk_aluno_usuario_ue` 
  FOREIGN KEY (`id_usuario_ue`) REFERENCES `usuarios_ue` (`id_usuario_ue`) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;

