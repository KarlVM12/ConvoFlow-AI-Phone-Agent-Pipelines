###############################################################################
# Terraform - PROJECT production stack                                         #
# - prod-server  : PHP 8.4 Elastic Beanstalk (private) behind a public ALB    #
# - remote-access: Tiny EC2 bastion with **static Elastic IP** for SSH/SQL    #
# - production-db: MySQL 8.0 RDS in private subnets                           #
# Network: new VPC, public+private subnets, IGW, NAT                           #
###############################################################################

terraform {
  required_version = ">= 1.8"
  required_providers {
    aws     = { source = "hashicorp/aws", version = "~> 5.50" }
    archive = { source = "hashicorp/archive", version = "~> 2.4" }
    tls     = { source = "hashicorp/tls", version = "~> 4.0" }
    random  = { source = "hashicorp/random", version = "~> 3.6" }
  }
}

provider "aws" {
  profile = "${var.project_name}"
  region = var.region
}

############################
# 1. Networking (VPC)      #
############################
resource "aws_vpc" "vpc" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_support   = true
  enable_dns_hostnames = true
  tags = { Name = "${var.project_name}-vpc" }
}

data "aws_availability_zones" "available" { state = "available" }

# Public subnets
resource "aws_subnet" "public" {
  count                   = 2
  vpc_id                  = aws_vpc.vpc.id
  cidr_block              = cidrsubnet(aws_vpc.vpc.cidr_block, 8, count.index)
  availability_zone       = data.aws_availability_zones.available.names[count.index]
  map_public_ip_on_launch = true
  tags = { Name = "${var.project_name}-public-${count.index}", Tier = "public" }
}

# Private subnets
resource "aws_subnet" "private" {
  count                   = 2
  vpc_id                  = aws_vpc.vpc.id
  cidr_block              = cidrsubnet(aws_vpc.vpc.cidr_block, 8, count.index + 8)
  availability_zone       = data.aws_availability_zones.available.names[count.index]
  map_public_ip_on_launch = false
  tags = { Name = "${var.project_name}-private-${count.index}", Tier = "private" }
}

# Internet gateway & RT for public subnets
resource "aws_internet_gateway" "igw" {
  vpc_id = aws_vpc.vpc.id
  tags   = { Name = "${var.project_name}-igw" }
}

resource "aws_route_table" "public" {
  vpc_id = aws_vpc.vpc.id
  route { 
    cidr_block = "0.0.0.0/0" 
    gateway_id = aws_internet_gateway.igw.id 
  }
  tags = { Name = "${var.project_name}-public-rt" }
}

resource "aws_route_table_association" "public" {
  count          = length(aws_subnet.public)
  subnet_id      = aws_subnet.public[count.index].id
  route_table_id = aws_route_table.public.id
}

# NAT for private subnets
resource "aws_eip" "nat_eip" { 
  domain = "vpc" 
  tags = { Name = "${var.project_name}-nat-eip" } 
}

resource "aws_nat_gateway" "nat" {
  allocation_id = aws_eip.nat_eip.id
  subnet_id     = aws_subnet.public[0].id
  depends_on    = [aws_internet_gateway.igw]
  tags          = { Name = "${var.project_name}-nat" }
}

resource "aws_route_table" "private" {
  vpc_id = aws_vpc.vpc.id
  route { 
    cidr_block = "0.0.0.0/0" 
    nat_gateway_id = aws_nat_gateway.nat.id 
  }
  tags = { Name = "${var.project_name}-private-rt" }
}

resource "aws_route_table_association" "private" {
  count          = length(aws_subnet.private)
  subnet_id      = aws_subnet.private[count.index].id
  route_table_id = aws_route_table.private.id
}

############################
# 2. Security groups       #
############################
resource "aws_security_group" "app_sg" {
  name   = "${var.project_name}-app-sg"
  vpc_id = aws_vpc.vpc.id
  egress { 
    from_port = 0 
    to_port = 0 
    protocol = "-1" 
    cidr_blocks = ["0.0.0.0/0"] 
  }
}

resource "aws_security_group" "bastion_sg" {
  name   = "${var.project_name}-bastion-sg"
  vpc_id = aws_vpc.vpc.id
  ingress { 
    from_port = 22 
    to_port = 22 
    protocol = "tcp" 
    cidr_blocks = ["0.0.0.0/0"] 
  }
  egress  { 
    from_port = 0  
    to_port = 0  
    protocol = "-1" 
    cidr_blocks = ["0.0.0.0/0"] 
  }
}

resource "aws_security_group" "db_sg" {
  name   = "${var.project_name}-db-sg"
  vpc_id = aws_vpc.vpc.id
  ingress {
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.app_sg.id, aws_security_group.bastion_sg.id]
  }
  egress { 
    from_port = 0 
    to_port = 0 
    protocol = "-1" 
    cidr_blocks = ["0.0.0.0/0"] 
  }
}

############################
# 3. RDS – production-db   #
############################
resource "aws_db_subnet_group" "mysql" {
  name       = "${lower(var.project_name)}-db-subnets"
  subnet_ids = aws_subnet.private[*].id
}

resource "random_password" "admin_pwd" { 
  length = 20 
  special = true
  override_special = "_#%+="
}

resource "aws_db_parameter_group" "mysql_custom" {
  name        = "mysql8-params-custom-${lower(var.project_name)}"
  family      = "mysql8.0"
  description = "Custom params for production-db (allows triggers to be created)"

  parameter {
    name         = "log_bin_trust_function_creators"
    value        = "1" 
    apply_method = "immediate"
  }
}

resource "aws_db_instance" "production" {
  identifier              = "production-db-${lower(var.project_name)}"
  db_name                 = "production_db_${lower(var.project_name)}"
  allocated_storage       = 20
  engine                  = "mysql"
  engine_version          = "8.0.36"
  instance_class          = "db.t4g.micro"
  username                = "admin"
  password                = random_password.admin_pwd.result
  db_subnet_group_name    = aws_db_subnet_group.mysql.name
  vpc_security_group_ids  = [aws_security_group.db_sg.id]
  skip_final_snapshot     = true
  publicly_accessible     = false
  parameter_group_name = aws_db_parameter_group.mysql_custom.name
}

############################
# 4. Bastion host (static) #
############################
resource "tls_private_key" "bastion" { 
  algorithm = "RSA" 
  rsa_bits = 4096 
}

resource "aws_key_pair" "bastion" {
  key_name   = "${var.project_name}-bastion-key"
  public_key = tls_private_key.bastion.public_key_openssh
}

data "aws_ami" "amazon_linux2" {
  most_recent = true
  owners      = ["amazon"]
  filter {
    name   = "name"
    values = ["amzn2-ami-kernel-*-hvm-*x86_64-gp2"]
  }
}

resource "aws_instance" "bastion" {
  ami                         = data.aws_ami.amazon_linux2.id
  instance_type               = "t3.micro"
  subnet_id                   = aws_subnet.public[0].id
  vpc_security_group_ids      = [aws_security_group.bastion_sg.id]
  key_name                    = aws_key_pair.bastion.key_name
  associate_public_ip_address = false   # we'll attach Elastic IP
  tags = { Name = "remote-access" }
}

resource "aws_eip" "bastion_ip" {
  domain = "vpc"
  instance = aws_instance.bastion.id
  tags     = { Name = "${var.project_name}-bastion-eip" }
}

############################
# 5. Package PHP sample    #
############################
locals { app_name = "${var.project_name}" }

resource "random_id" "suffix" { byte_length = 4 }

data "archive_file" "app_zip" {
  type        = "zip"
  output_path = "${path.module}/app.zip"
  source_dir  = "${path.module}/sample_app"
}

resource "aws_s3_bucket" "eb_bucket" {
  bucket        = "${lower(var.project_name)}-eb-${random_id.suffix.hex}"
  force_destroy = true
}

resource "aws_s3_object" "app_source" {
  bucket = aws_s3_bucket.eb_bucket.id
  key    = "app.zip"
  source = data.archive_file.app_zip.output_path
  etag   = filemd5(data.archive_file.app_zip.output_path)
}

############################
# 6. Elastic Beanstalk     #
############################

############################
# IAM – EC2 role for EB
############################
resource "aws_iam_role" "eb_ec2_role" {
  name = "${var.project_name}-eb-ec2-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Principal = { Service = "ec2.amazonaws.com" }
      Action = "sts:AssumeRole"
    }]
  })
}

resource "aws_iam_role_policy_attachment" "eb_web_tier" {
  role       = aws_iam_role.eb_ec2_role.name
  policy_arn = "arn:aws:iam::aws:policy/AWSElasticBeanstalkWebTier"
}

resource "aws_iam_role_policy_attachment" "eb_ecr" {
  role       = aws_iam_role.eb_ec2_role.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonEC2ContainerRegistryReadOnly"
}

resource "aws_iam_instance_profile" "eb_instance_profile" {
  name = "${var.project_name}-eb-ec2-instance-profile"
  role = aws_iam_role.eb_ec2_role.name
}

############################
#  Elastic Beanstalk Envs  #   
############################
resource "aws_elastic_beanstalk_application" "elb_app" {
  name        = local.app_name
  description = "Production application for ${var.project_name}"
}

resource "aws_elastic_beanstalk_application_version" "v1" {
  name        = "v1"
  application = aws_elastic_beanstalk_application.elb_app.name
  bucket      = aws_s3_bucket.eb_bucket.id
  key         = aws_s3_object.app_source.key
}

resource "aws_elastic_beanstalk_environment" "prod_server" {
  name                = "prod-server-${lower(var.project_name)}"
  application         = aws_elastic_beanstalk_application.elb_app.name
  solution_stack_name = "64bit Amazon Linux 2023 v4.6.2 running PHP 8.4"
  version_label       = aws_elastic_beanstalk_application_version.v1.name

  setting { 
    namespace = "aws:ec2:vpc" 
    name = "VPCId"             
    value = aws_vpc.vpc.id 
  }
  setting { 
    namespace = "aws:ec2:vpc" 
    name = "Subnets"            
    value = join(",", aws_subnet.private[*].id) 
  }
  setting { 
    namespace = "aws:ec2:vpc" 
    name = "ELBSubnets"
    value = join(",", aws_subnet.public[*].id) 
  }
  # setting { 
  #   namespace = "aws:ec2:vpc" 
  #   name = "PublicLoadBalancer" 
  #   value = "true" 
  # }
  setting {
    namespace = "aws:elbv2:loadbalancer"
    name      = "Scheme"
    value     = "internet-facing"
  }
  setting {
    namespace = "aws:elasticbeanstalk:environment"
    name      = "LoadBalancerType"
    value     = "application"
  }
  setting {
    namespace = "aws:elbv2:listener:default"
    name      = "ListenerEnabled"
    value     = "true"
  }
  setting {
    namespace = "aws:elbv2:listener:default"
    name      = "Protocol"
    value     = "HTTP"
  }
  setting {
    namespace = "aws:elbv2:listener:default"
    name      = "Port"
    value     = "80"
  }
  setting { 
    namespace = "aws:autoscaling:launchconfiguration" 
    name = "InstanceType"  
    value = "t4g.micro" 
  }
  setting {
    namespace = "aws:autoscaling:launchconfiguration"
    name = "IamInstanceProfile"
    value = aws_iam_instance_profile.eb_instance_profile.name
  }
  setting { 
    namespace = "aws:autoscaling:asg"                 
    name = "MinSize" 
    value = "1" 
  }
  setting { 
    namespace = "aws:autoscaling:asg"                 
    name = "MaxSize" 
    value = "1" 
  }
  setting { 
    namespace = "aws:elasticbeanstalk:environment"    
    name = "EnvironmentType" 
    value = "LoadBalanced" 
  }
  setting { 
    namespace = "aws:autoscaling:launchconfiguration" 
    name = "SecurityGroups" 
    value = "${aws_security_group.app_sg.id},${aws_security_group.db_sg.id}"
  }
  setting { 
    namespace = "aws:elasticbeanstalk:application:environment" 
    name = "DB_HOST"     
    value = aws_db_instance.production.address 
  }
  setting { 
    namespace = "aws:elasticbeanstalk:application:environment" 
    name = "DB_NAME"     
    value = aws_db_instance.production.db_name 
  }
  setting { 
    namespace = "aws:elasticbeanstalk:application:environment" 
    name = "DB_USER"     
    value = "admin" 
  }
  # setting { 
  #   namespace = "aws:elasticbeanstalk:application:environment" 
  #   name = "DB_PASSWORD" 
  #   value = random_password.admin_pwd.result 
  # }
  setting { 
    namespace = "aws:elasticbeanstalk:container:php:phpini" 
    name = "document_root" 
    value = "/public" 
  }
  setting { 
    namespace = "aws:elasticbeanstalk:container:php:phpini" 
    name = "memory_limit"  
    value = "768M" 
  }
  setting {
    namespace = "aws:elasticbeanstalk:command"
    name      = "IgnoreHealthCheck"
    value     = "true"
  }
  setting {
    namespace = "aws:autoscaling:launchconfiguration"
    name      = "EC2KeyName"
    value     = aws_key_pair.bastion.key_name
  }
  setting {
    namespace = "aws:ec2:vpc"
    name      = "AssociatePublicIpAddress"
    value     = "false"
  }
  setting {
    namespace = "aws:elasticbeanstalk:environment:proxy"
    name      = "ProxyServer"
    value     = "apache"
  }
}

############################
# 7. Variables & Outputs   #
############################
# variable "region" {
#   description = "AWS Region"
#   type        = string
#   default     = "us-east-1"
# }
#
# output "prod_site_url" {
#   value       = aws_elastic_beanstalk_environment.prod_server.endpoint_url
#   description = "URL of the public ALB for prod-server"
# }
#
# output "bastion_ssh_host" {
#   value       = aws_eip.bastion_ip.public_ip
#   description = "Elastic IP for SSH into remote-access bastion"
# }
#
# output "bastion_private_key_pem" {
#   value       = tls_private_key.bastion.private_key_pem
#   sensitive   = true
#   description = "PEM private key for the bastion EC2"
# }
#
# output "rds_endpoint" {
#   value       = aws_db_instance.production.address
#   description = "Hostname of the MySQL production-db"
# }
#
# output "rds_admin_password" {
#   value       = random_password.admin_pwd.result
#   sensitive   = true
#   description = "Admin password for MySQL"
# }
