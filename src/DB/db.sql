CREATE TABLE users (
       id INTEGER PRIMARY KEY AUTOINCREMENT,
       mail                 text NOT NULL,
       request_token        text,
       token_secret         text,
       created              text,
       modified             text,
       deleted              text
);

