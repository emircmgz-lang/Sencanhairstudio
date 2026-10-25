const fs=require('node:fs'),path=require('node:path'),{DatabaseSync}=require('node:sqlite'),{defaultBarbers,fullBarber}=require('./domain.cjs');
function initialBusiness(){const barbers=defaultBarbers.map(fullBarber);barbers[0].schedule[1]={...barbers[0].schedule[1],start:'18:00',end:'22:00'};barbers[3].schedule[2]={...barbers[3].schedule[2],start:'18:00',end:'22:00'};return {name:'Davut Şencan Hair Studio',description:'Kendinize bir saat ayırın. Gerisini bize bırakın.',phone:'',address:'',barbers,scheduleRevision:1,brandNameRevision:1}}
function openStore(dbPath){fs.mkdirSync(path.dirname(dbPath),{recursive:true,mode:0o700});const db=new DatabaseSync(dbPath);db.exec(`PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000;
CREATE TABLE IF NOT EXISTS business(id INTEGER PRIMARY KEY CHECK(id=1),json TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS admin(id INTEGER PRIMARY KEY CHECK(id=1),salt TEXT NOT NULL,password_hash TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS sessions(token_hash TEXT PRIMARY KEY,csrf TEXT NOT NULL,expires INTEGER NOT NULL);
CREATE TABLE IF NOT EXISTS appointments(id TEXT PRIMARY KEY,code TEXT NOT NULL UNIQUE,barber_id INTEGER NOT NULL,date TEXT NOT NULL,start INTEGER NOT NULL,duration INTEGER NOT NULL,name TEXT NOT NULL,phone TEXT NOT NULL,payment_method TEXT NOT NULL,status TEXT NOT NULL CHECK(status IN ('confirmed','cancelled','completed')),created_at TEXT NOT NULL,request_key TEXT UNIQUE,request_hash TEXT);
CREATE INDEX IF NOT EXISTS appointment_day ON appointments(date,barber_id,status);
CREATE UNIQUE INDEX IF NOT EXISTS appointment_start ON appointments(barber_id,date,start) WHERE status!='cancelled';
CREATE TABLE IF NOT EXISTS email_outbox(id TEXT PRIMARY KEY,booking_id TEXT NOT NULL REFERENCES appointments(id),status TEXT NOT NULL,attempts INTEGER NOT NULL DEFAULT 0,next_attempt INTEGER NOT NULL DEFAULT 0,last_error TEXT,created_at TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS audit(id INTEGER PRIMARY KEY,action TEXT NOT NULL,entity_id TEXT,created_at TEXT NOT NULL);
`);const columns=new Set(db.prepare('PRAGMA table_info(appointments)').all().map(c=>c.name));if(!columns.has('services_json'))db.exec("ALTER TABLE appointments ADD COLUMN services_json TEXT NOT NULL DEFAULT '[]'");if(!columns.has('total'))db.exec('ALTER TABLE appointments ADD COLUMN total INTEGER');if(!columns.has('price_from'))db.exec('ALTER TABLE appointments ADD COLUMN price_from INTEGER NOT NULL DEFAULT 0');if(!db.prepare('SELECT 1 FROM business WHERE id=1').get())db.prepare('INSERT INTO business VALUES(1,?)').run(JSON.stringify(initialBusiness()));try{fs.chmodSync(dbPath,0o600)}catch{}return db}
function businessGet(db){return JSON.parse(db.prepare('SELECT json FROM business WHERE id=1').get().json)}
function businessSet(db,b){db.prepare('UPDATE business SET json=? WHERE id=1').run(JSON.stringify(b))}
function bookingFromRow(a){if(!a)return null;const {clock}=require('./domain.cjs');return {id:a.id,code:a.code,barberId:a.barber_id,date:a.date,time:clock(a.start),duration:a.duration,name:a.name,phone:a.phone,paymentMethod:a.payment_method,status:a.status,paymentStatus:'unpaid',createdAt:a.created_at,services:JSON.parse(a.services_json||'[]'),total:a.total??null,priceFrom:!!a.price_from}}
function bookingsFor(db,date){return db.prepare('SELECT * FROM appointments WHERE date=? ORDER BY start,barber_id').all(date).map(bookingFromRow)}
function audit(db,action,id=null){db.prepare('INSERT INTO audit(action,entity_id,created_at) VALUES(?,?,?)').run(action,id,new Date().toISOString())}
function transaction(db,fn){db.exec('BEGIN IMMEDIATE');try{const result=fn();db.exec('COMMIT');return result}catch(e){db.exec('ROLLBACK');throw e}}
module.exports={openStore,businessGet,businessSet,bookingFromRow,bookingsFor,audit,transaction};
