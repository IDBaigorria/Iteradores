# Migraciones aplicadas:

## 12/10/26
index.php?migrar_a_sql64=1
index.php?migrar_usuarios_especiales=1
index.php?migrar_niveles_usuario=1
index.php?migrar_compartidos_terminal=1
index.php?construir_arboles_compartidos=1
index.php?repuntar_terminales_compartido=1 
index.php?migrar_topologia=1
index.php?reparar_topologia=1

### siginificado: 

index.php?migrar_a_sql64=1 → pasa SQL → SQL64. Es el paso crítico. Tiene que decir "migrado. Contextos: N" para los dos grafos (app y credenciales).
index.php?migrar_usuarios_especiales=1 → convierte usuarios a us_<nombre>.
index.php?migrar_niveles_usuario=1 → crea publico/privado en cada usuario.
index.php?migrar_compartidos_terminal=1 → crea compartido_con_X en cada dueño.
index.php?construir_arboles_compartidos=1 → árboles paralelos en compartidos.
index.php?repuntar_terminales_compartido=1 → repunta dueno del terminal al compartido.
index.php?migrar_topologia=1 → us_Admin → usuarios + dueno → soporte → publico.
index.php?reparar_topologia=1 → reparación final (por si algo quedó desenlazado).

## 13/1023
index.php?reparar_arboles=1
