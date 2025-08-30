output "prod_site_url" {
  value       = aws_elastic_beanstalk_environment.prod_server.endpoint_url
  description = "URL of the public ALB for prod-server"
}

output "bastion_ssh_host" {
  value       = aws_eip.bastion_ip.public_ip
  description = "Elastic IP for SSH into remote-access bastion"
}

output "bastion_private_key_pem" {
  value       = tls_private_key.bastion.private_key_pem
  sensitive   = true
  description = "PEM private key for the bastion EC2"
}

output "rds_endpoint" {
  value       = aws_db_instance.production.address
  description = "Hostname of the MySQL production-db"
}

output "rds_admin_password" {
  value       = random_password.admin_pwd.result
  sensitive   = true
  description = "Admin password for MySQL"
}
