Refactor phpimageresize
=======

Doy por terminado el ejercicio, que se alargó demasiado. 

Notas
------
  * Creo que se podría optimizar los test que tengo desarrollados, repiten demasiado código. Además, sería interesante, cambiar la forma en que son creados para no obligar a las clases a tener métodos públicos exclusivamente para los test.

  * No he quedado muy contento con el movimiento que realice a la hora de mover, a ImagePath, la función que comprueba la existencia de NewPath. No está realizando una comprobación de path, esta comprobando la existencia de un fichero. Aunque ImagePath reclama la acción a Cache, no estoy muy seguro de que encaje bien ahí. El movimiento de la función vino originado, por la eliminación que Resizer tenia sobre Cache. Ahora ImagePath es el único que depende de Cache.
  
  * Lleve la función pathinfo a Cache, eliminando la dependencia que tenía ImagePath sobre FileSystem. Ahora solo cache depende de FileSystem. No tengo claro que deba ser una responsabilidad de Cache, quizás debería ser de otro nuevo objeto.
  

  
  
Algo así como ... un diagrama de dependencias entre objetos
------

		      Resizer
		    
    Configuration	Command		  ImagePath
					
				    ComposePath	  Cache
					
						    FileSystem
