const { Sequelize } = require('sequelize');
module.exports = new Sequelize('eco_db', 'db_user', 'db_pass', {
  host: 'localhost',
  dialect: 'mysql',
  logging: false
});
