SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson)
WHERE a.desemail = 'admin@hcode.com.br';