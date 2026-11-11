-- ITAM & Helpdesk - Dữ liệu mẫu (toàn bộ là dữ liệu giả lập, phục vụ mục đích học tập).
-- Nhập SAU schema.sql: mysql -u USER -p TEN_CSDL < seed.sql

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

/*!40000 ALTER TABLE `asset_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_assignments` ENABLE KEYS */;

/*!40000 ALTER TABLE `asset_categories` DISABLE KEYS */;
INSERT INTO `asset_categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES (1,'Laptop','May tinh xach tay','2026-11-08 19:35:34','2026-11-08 19:35:34'),(2,'Desktop','May tinh de ban','2026-11-08 19:35:34','2026-11-08 19:35:34'),(3,'Monitor','Man hinh may tinh','2026-11-08 19:35:34','2026-11-08 19:35:34'),(4,'Printer','May in','2026-11-08 19:35:34','2026-11-08 19:35:34'),(5,'Network Device','Thiet bi mang','2026-11-08 19:35:34','2026-11-08 19:35:34');
/*!40000 ALTER TABLE `asset_categories` ENABLE KEYS */;

/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` (`id`, `asset_code`, `name`, `category_id`, `serial_number`, `brand`, `model`, `purchase_date`, `purchase_price`, `status`, `description`, `created_at`, `updated_at`, `parent_id`) VALUES (1,'TS001','Dell Latitude 5440',1,'DELL-LAP-001','Dell','Latitude 5440','2025-01-15',18000000.00,'available','Laptop phuc vu cong viec','2026-11-08 19:35:34','2026-11-08 19:35:34',NULL),(2,'TS002','HP ProDesk 400',2,'HP-PC-002','HP','ProDesk 400','2025-02-10',15000000.00,'available','May tinh de ban van phong','2026-11-08 19:35:34','2026-11-08 19:35:34',NULL),(3,'TS003','Dell P2422H',3,'DELL-MON-003','Dell','P2422H','2025-03-05',4500000.00,'available','Man hinh may tinh','2026-11-08 19:35:34','2026-11-08 19:35:34',NULL),(4,'TS004','HP LaserJet Pro',4,'HP-PRINT-004','HP','LaserJet Pro','2025-04-12',6500000.00,'available','May in tai phong hanh chinh','2026-11-08 19:35:34','2026-11-08 19:35:34',NULL),(5,'TS005','TP-Link Router',5,'TPL-ROUTER-005','TP-Link','Archer','2025-05-20',1200000.00,'available','Thiet bi mang noi bo','2026-11-08 19:35:34','2026-11-08 19:35:34',NULL),(6,'SRV001','Máy chủ ứng dụng Rack 1',5,NULL,NULL,NULL,NULL,NULL,'available',NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34',NULL),(7,'SRV001-ST1','Khay lưu trữ RAID 1',5,NULL,NULL,NULL,NULL,NULL,'available',NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34',6),(8,'SRV001-ST1-D1','Ổ đĩa SSD 1 TB (vị trí 1)',5,NULL,NULL,NULL,NULL,NULL,'available',NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34',7),(9,'SRV001-ST1-D2','Ổ đĩa SSD 1 TB (vị trí 2)',5,NULL,NULL,NULL,NULL,NULL,'available',NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34',7),(10,'SRV001-NIC','Card mạng 10 GbE',5,NULL,NULL,NULL,NULL,NULL,'available',NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34',6);
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;

/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES (1,'IT','Phong cong nghe thong tin','2026-11-08 19:35:34','2026-11-08 19:35:34'),(2,'HR','Phong nhan su','2026-11-08 19:35:34','2026-11-08 19:35:34'),(3,'Accounting','Phong ke toan','2026-11-08 19:35:34','2026-11-08 19:35:34');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;

/*!40000 ALTER TABLE `escalations` DISABLE KEYS */;
/*!40000 ALTER TABLE `escalations` ENABLE KEYS */;

/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` (`id`, `holiday_date`, `name`, `created_at`, `updated_at`) VALUES (1,'2026-09-02','Quốc khánh','2026-11-08 19:35:34','2026-11-08 19:35:34'),(2,'2027-01-01','Tết Dương lịch','2026-11-08 19:35:34','2026-11-08 19:35:34'),(3,'2027-02-05','Tết Nguyên đán (minh họa)','2026-11-08 19:35:34','2026-11-08 19:35:34'),(4,'2027-02-08','Tết Nguyên đán (minh họa)','2026-11-08 19:35:34','2026-11-08 19:35:34'),(5,'2027-02-09','Tết Nguyên đán (minh họa)','2026-11-08 19:35:34','2026-11-08 19:35:34'),(6,'2027-04-30','Ngày Giải phóng miền Nam','2026-11-08 19:35:34','2026-11-08 19:35:34'),(7,'2027-05-01','Quốc tế Lao động','2026-11-08 19:35:34','2026-11-08 19:35:34'),(8,'2027-09-02','Quốc khánh','2026-11-08 19:35:34','2026-11-08 19:35:34');
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;

/*!40000 ALTER TABLE `maintenances` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenances` ENABLE KEYS */;

/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES (1,'Admin','Quan tri he thong','2026-11-08 19:35:34','2026-11-08 19:35:34'),(2,'Technician','Nhan vien ky thuat','2026-11-08 19:35:34','2026-11-08 19:35:34'),(3,'Employee','Nhan vien','2026-11-08 19:35:34','2026-11-08 19:35:34');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;

/*!40000 ALTER TABLE `sla_policies` DISABLE KEYS */;
INSERT INTO `sla_policies` (`id`, `priority`, `name`, `resolution_minutes`, `created_at`, `updated_at`) VALUES (1,'urgent','Khẩn cấp - xử lý trong 2 giờ làm việc',120,'2026-11-08 19:35:34','2026-11-08 19:35:34'),(2,'high','Cao - xử lý trong 4 giờ làm việc',240,'2026-11-08 19:35:34','2026-11-08 19:35:34'),(3,'medium','Trung bình - xử lý trong 8 giờ làm việc (1 ngày)',480,'2026-11-08 19:35:34','2026-11-08 19:35:34'),(4,'low','Thấp - xử lý trong 24 giờ làm việc (3 ngày)',1440,'2026-11-08 19:35:34','2026-11-08 19:35:34');
/*!40000 ALTER TABLE `sla_policies` ENABLE KEYS */;

/*!40000 ALTER TABLE `ticket_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_attachments` ENABLE KEYS */;

/*!40000 ALTER TABLE `ticket_categories` DISABLE KEYS */;
INSERT INTO `ticket_categories` (`id`, `created_at`, `updated_at`, `name`, `description`, `is_active`) VALUES (1,'2026-11-08 19:35:34','2026-11-08 19:35:34','Hardware','Su co phan cung may tinh va thiet bi',1),(2,'2026-11-08 19:35:34','2026-11-08 19:35:34','Software','Loi phan mem va ung dung',1),(3,'2026-11-08 19:35:34','2026-11-08 19:35:34','Network','Su co ket noi mang va Internet',1),(4,'2026-11-08 19:35:34','2026-11-08 19:35:34','Account','Yeu cau cap va khoi phuc tai khoan',1),(5,'2026-11-08 19:35:34','2026-11-08 19:35:34','Equipment Request','Yeu cau cap phat thiet bi CNTT',1);
/*!40000 ALTER TABLE `ticket_categories` ENABLE KEYS */;

/*!40000 ALTER TABLE `ticket_comments` DISABLE KEYS */;
INSERT INTO `ticket_comments` (`id`, `ticket_id`, `user_id`, `content`, `created_at`, `updated_at`) VALUES (1,1,3,'Laptop cua toi khong khoi dong tu sang nay.','2026-11-08 19:35:34','2026-11-08 19:35:34'),(2,1,2,'Toi se kiem tra nguon va pin cua laptop.','2026-11-08 19:35:34','2026-11-08 19:35:34'),(3,2,2,'Dang kiem tra loi phan mem.','2026-11-08 19:35:34','2026-11-08 19:35:34'),(4,4,2,'Da ho tro khoi phuc tai khoan.','2026-11-08 19:35:34','2026-11-08 19:35:34');
/*!40000 ALTER TABLE `ticket_comments` ENABLE KEYS */;

/*!40000 ALTER TABLE `ticket_histories` DISABLE KEYS */;
INSERT INTO `ticket_histories` (`id`, `ticket_id`, `user_id`, `action`, `old_status`, `new_status`, `description`, `created_at`, `updated_at`) VALUES (1,1,3,'created',NULL,'open','Nhan vien tao phieu ho tro.','2026-11-08 19:35:34','2026-11-08 19:35:34'),(2,1,2,'assigned','open','assigned','Phieu duoc giao cho nhan vien ky thuat.','2026-11-08 19:35:34','2026-11-08 19:35:34'),(3,2,2,'status_changed','assigned','in_progress','Ky thuat vien bat dau xu ly.','2026-11-08 19:35:34','2026-11-08 19:35:34'),(4,4,2,'status_changed','in_progress','resolved','Phieu ho tro da duoc giai quyet.','2026-11-08 19:35:34','2026-11-08 19:35:34');
/*!40000 ALTER TABLE `ticket_histories` ENABLE KEYS */;

/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
INSERT INTO `tickets` (`id`, `ticket_code`, `created_by`, `assigned_to`, `category_id`, `asset_id`, `title`, `description`, `priority`, `status`, `resolved_at`, `closed_at`, `created_at`, `updated_at`, `sla_due_at`, `sla_paused_at`, `sla_remaining_minutes`, `sla_escalated_at`) VALUES (1,'YC001',3,2,1,1,'Laptop khong khoi dong','Laptop bam nut nguon nhung khong khoi dong.','high','assigned',NULL,NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34','2026-11-09 05:00:00',NULL,NULL,NULL),(2,'YC002',3,2,2,2,'Khong mo duoc phan mem','Phan mem lam viec gap loi khi khoi dong.','medium','in_progress',NULL,NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34','2026-11-09 09:00:00',NULL,NULL,NULL),(3,'YC003',3,NULL,3,NULL,'Mat ket noi Internet','May tinh khong truy cap duoc Internet.','urgent','open',NULL,NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34','2026-11-09 03:00:00',NULL,NULL,NULL),(4,'YC004',3,2,4,NULL,'Yeu cau khoi phuc tai khoan','Nhan vien can ho tro khoi phuc tai khoan.','low','resolved','2026-11-08 19:35:34',NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34','2026-11-11 07:00:00',NULL,NULL,NULL),(5,'YC005',3,NULL,5,3,'Yeu cau kiem tra man hinh','Man hinh hien thi khong on dinh.','medium','open',NULL,NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34','2026-11-09 09:00:00',NULL,NULL,NULL);
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `employee_code`, `name`, `email`, `phone`, `role_id`, `department_id`, `is_active`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `failed_login_attempts`, `locked_until`) VALUES (1,'NV001','Admin IT','admin@example.com','0900000001',1,1,1,NULL,'$2y$12$4ViQbmPagG6TH5q1QzhzJOP24dGBS24W4i/rhkAOOtIprLu1qJc4m',NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34',0,NULL),(2,'NV002','Technician IT','technician@example.com','0900000002',2,1,1,NULL,'$2y$12$ARIUKQFfvo/yrUd0pcxnl.hhDD1i8jos5TSofs8gCfUs9PjhCLbku',NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34',0,NULL),(3,'NV003','Employee Demo','employee@example.com','0900000003',3,2,1,NULL,'$2y$12$tDqYrNSvTXMrKCyxneJGV.KF1gELwD0XcyPPi5ENg3nO0DBA554Fa',NULL,'2026-11-08 19:35:34','2026-11-08 19:35:34',0,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

