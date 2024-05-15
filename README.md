# cofeeshop


## Proje Kurulumu

Bu bölüm, Laravel tabanlı cofeeshop projenizin nasıl kurulacağını adım adım açıklamaktadır.

### Ön Koşullar

Projeyi kurmadan önce sisteminizde aşağıdaki araçların kurulu olduğundan emin olun:

- PHP (8.2 veya daha yeni)
- Composer
- MySQL veya başka bir veritabanı sistemi

### Kurulum Adımları

#### 1. Proje Dosyalarını İndirme

Projeyi GitHub'dan klonlayın veya indirin:

`git clone`


Klonlama işlemi tamamlandıktan sonra, proje dizinine gidin:

`cd yeginbatuhan`


#### 2. Bağımlılıkları Yükleme

Composer aracılığıyla PHP bağımlılıklarını yükleyin:

`composer install`


#### 3. .env Dosyasını Ayarlama

`.env.example` dosyasının bir kopyasını `.env` olarak oluşturun ve veritabanı bağlantı bilgilerinizi girin:

`cp .env.example .env`


Ardından, `.env` dosyasını düzenleyin ve veritabanı bilgilerinizi ekleyin


#### 4. Uygulama Anahtarını Oluşturma

Laravel uygulama anahtarını oluşturmak için aşağıdaki komutu çalıştırın:

`php artisan key:generate`


#### 5. Veritabanı Migrasyonları

Veritabanı tablolarını oluşturmak ve varsayılan kullanıcılar gibi bazı verileri eklemek için migrasyonları çalıştırın:

`php artisan migrate --seed`


#### 6. Passport Install

Laravel/Passport ile Auth İşlemleri İçin çalıştırın:

`php artisan passport:client --personal`

#### 7. Sunucuyu Başlatma

Geliştirme sunucusunu başlatmak için:

`php artisan serve`

## Proje Postman Docs

Postman Dökümantasyonuna Ulaşmak için: `https://documenter.getpostman.com/view/26732863/2sA3JRYeJq` 

## Proje Notları

Proje için gerekli testlerin tamamını denetlemedim. Bundan dolayı olası olarak karşılaşacağınız hatalardan dolayı kusuruma bakmayınız.
