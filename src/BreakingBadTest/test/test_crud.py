from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import unittest
import time
import os
from datetime import datetime
import traceback
from urllib.parse import quote

class TestReportGenerator:
    @staticmethod
    def generate_html_report(test_results, screenshots_dir):
        timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
        report_title = "Reporte de Pruebas - Breaking Bad CRUD"
        
        report_dir = os.path.abspath("reports")
        screenshots_abs_dir = os.path.abspath(screenshots_dir)
        
        html_template = f"""
        <!DOCTYPE html>
        <html>
        <head>
            <title>{report_title}</title>
            <meta charset="UTF-8">
            <style>
                body {{ font-family: Arial, sans-serif; margin: 20px; }}
                h1 {{ color: #2c3e50; }}
                .test-case {{ margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }}
                .passed {{ background-color: #d4edda; border-color: #c3e6cb; }}
                .failed {{ background-color: #f8d7da; border-color: #f5c6cb; }}
                .error {{ background-color: #fff3cd; border-color: #ffeeba; }}
                .screenshot {{ margin-top: 10px; border: 1px solid #aaa; max-width: 100%; height: auto; }}
                .summary {{ background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; }}
                .timestamp {{ color: #6c757d; font-size: 0.9em; }}
                .error-message {{ color: #dc3545; font-weight: bold; }}
                .stack-trace {{ font-family: monospace; white-space: pre; background-color: #f8f9fa; padding: 10px; }}
                .screenshot-container {{ margin: 10px 0; }}
                .screenshot-name {{ font-size: 0.8em; color: #666; }}
            </style>
        </head>
        <body>
            <h1>{report_title}</h1>
            <div class="timestamp">Generado: {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}</div>
            
            <div class="summary">
                <h3>Resumen</h3>
                <p>Pruebas ejecutadas: {test_results['total']}</p>
                <p>Pruebas exitosas: <span style="color: green;">{test_results['passed']}</span></p>
                <p>Pruebas fallidas: <span style="color: red;">{test_results['failed']}</span></p>
                <p>Tiempo total de ejecución: {test_results['total_time']:.2f} segundos</p>
            </div>
        """

        for test in test_results['tests']:
            status_class = "passed" if test['status'] == "PASS" else "failed"
            if test['status'] == "ERROR":
                status_class = "error"
                
            html_template += f"""
            <div class="test-case {status_class}">
                <h3>{test['name']}</h3>
                <p><strong>Estado:</strong> {test['status']}</p>
                <p><strong>Duración:</strong> {test['duration']:.2f} segundos</p>
                <p><strong>Descripción:</strong> {test['description']}</p>
            """
            
            if test['status'] != "PASS":
                html_template += f"""
                <div class="error-message">Error: {test['message']}</div>
                <div class="stack-trace">{test['traceback']}</div>
                """
                
            if test['screenshots']:
                html_template += "<h4>Capturas de pantalla:</h4>"
                for screenshot in test['screenshots']:
                    if os.path.exists(screenshot):
                        rel_path = os.path.relpath(screenshot, report_dir)
                        encoded_path = quote(rel_path.replace(os.sep, '/'))
                        html_template += f"""
                        <div class="screenshot-container">
                            <img class="screenshot" src="{encoded_path}" alt="Captura de {test['name']}">
                            <div class="screenshot-name">{os.path.basename(screenshot)}</div>
                        </div>
                        """
            html_template += "</div>"

        html_template += """
        </body>
        </html>
        """

        os.makedirs(report_dir, exist_ok=True)
        report_filename = f"test_report_{timestamp}.html"
        report_path = os.path.join(report_dir, report_filename)

        with open(report_path, "w", encoding="utf-8") as f:
            f.write(html_template)

        return report_path

class BreakingBadCRUDTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        options = webdriver.ChromeOptions()
        download_dir = os.path.abspath("downloads")
        os.makedirs(download_dir, exist_ok=True)

        prefs = {
            "download.default_directory": download_dir,
            "download.prompt_for_download": False,
            "download.directory_upgrade": True,
            "plugins.always_open_pdf_externally": True
        }
        options.add_experimental_option("prefs", prefs)

        service = Service(ChromeDriverManager().install())
        cls.driver = webdriver.Chrome(service=service, options=options)
        cls.driver.maximize_window()
        cls.base_url = "http://localhost/BreakingBadweb"
        cls.screenshots_dir = "screenshots"
        cls.download_dir = download_dir
        
        cls.test_results = {
            'total': 0,
            'passed': 0,
            'failed': 0,
            'total_time': 0,
            'tests': []
        }
        os.makedirs(cls.screenshots_dir, exist_ok=True)
        cls.start_time = time.time()

    def setUp(self):
        self.test_start_time = time.time()
        self.current_test_screenshots = []
        self.test_result = {
            'name': self._testMethodName,
            'description': self.shortDescription() or "Sin descripción",
            'status': 'PASS',
            'message': '',
            'traceback': '',
            'duration': 0,
            'screenshots': []
        }

    def take_screenshot(self, name):
        safe_test_name = self._testMethodName.replace("/", "_").replace(" ", "_")
        timestamp = time.strftime("%Y%m%d_%H%M%S")
        filename = f"{safe_test_name}_{name}_{timestamp}.png"
        screenshot_path = os.path.join(self.screenshots_dir, filename)
        
        os.makedirs(self.screenshots_dir, exist_ok=True)
        
        try:
            self.driver.save_screenshot(screenshot_path)
            if os.path.exists(screenshot_path):
                self.current_test_screenshots.append(screenshot_path)
                return screenshot_path
            else:
                print(f"Advertencia: No se pudo guardar la captura en {screenshot_path}")
                return None
        except Exception as e:
            print(f"Error al tomar captura de pantalla: {str(e)}")
            return None

    def tearDown(self):
        self.test_result['duration'] = time.time() - self.test_start_time
        self.test_result['screenshots'] = [s for s in self.current_test_screenshots if os.path.exists(s)]
        
        cls = self.__class__
        cls.test_results['total'] += 1
        
        if hasattr(self, '_outcome'):  
            result = self._outcome.result
            if result.errors and result.errors[-1][1] is not None:
                self.test_result['status'] = 'ERROR'
                self.test_result['message'] = str(result.errors[-1][1])
                self.test_result['traceback'] = '\n'.join(
                    traceback.format_tb(result.errors[-1][2]))
                cls.test_results['failed'] += 1
            elif result.failures and result.failures[-1][1] is not None:
                self.test_result['status'] = 'FAIL'
                self.test_result['message'] = str(result.failures[-1][1])
                self.test_result['traceback'] = '\n'.join(
                    traceback.format_tb(result.failures[-1][2]))
                cls.test_results['failed'] += 1
            else:
                cls.test_results['passed'] += 1
        else:  # Python 2.7
            if not hasattr(self, '_resultForDoCleanups'):
                self._resultForDoCleanups = self.defaultTestResult()
            result = self._resultForDoCleanups
            if result.errors and result.errors[-1][1] is not None:
                self.test_result['status'] = 'ERROR'
                self.test_result['message'] = str(result.errors[-1][1])
                self.test_result['traceback'] = '\n'.join(
                    traceback.format_tb(result.errors[-1][2]))
                cls.test_results['failed'] += 1
            elif result.failures and result.failures[-1][1] is not None:
                self.test_result['status'] = 'FAIL'
                self.test_result['message'] = str(result.failures[-1][1])
                self.test_result['traceback'] = '\n'.join(
                    traceback.format_tb(result.failures[-1][2]))
                cls.test_results['failed'] += 1
            else:
                cls.test_results['passed'] += 1
        
        cls.test_results['tests'].append(self.test_result)

    @classmethod
    def tearDownClass(cls):
        cls.test_results['total_time'] = time.time() - cls.start_time
        cls.driver.quit()
        
        report_path = TestReportGenerator.generate_html_report(cls.test_results, cls.screenshots_dir)
        print(f"\nReporte generado en: file://{os.path.abspath(report_path)}")

    def test_1_login(self):
        """Test de inicio de sesión exitoso"""
        self.driver.get(f"{self.base_url}/login.php")
        self.take_screenshot("pantalla_login")
        self.driver.find_element(By.NAME, "username").send_keys("test")
        self.driver.find_element(By.NAME, "password").send_keys("password")
        self.take_screenshot("login_datos_ingresados")

        self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()

        WebDriverWait(self.driver, 10).until(
            EC.presence_of_element_located((By.XPATH, "//h2[contains(text(),'Personajes')]"))
        )
        self.take_screenshot("login_exitoso")
        self.assertIn("index.php", self.driver.current_url)

    def test_2_crear_personaje(self):
        """Test de creación de nuevo personaje"""
        self.driver.get(f"{self.base_url}/index.php")
        self.driver.find_element(By.LINK_TEXT, "Agregar Personaje").click()
        self.take_screenshot("crear_personaje_antes")

        WebDriverWait(self.driver, 10).until(
            EC.presence_of_element_located((By.XPATH, "//h2[contains(text(),'Agregar')]"))
        )

        self.driver.find_element(By.NAME, "nombre").send_keys("Walter White")
        self.driver.find_element(By.NAME, "color").send_keys("#FFFFFF")
        self.driver.find_element(By.NAME, "tipo").send_keys("Protagonista")
        self.driver.find_element(By.NAME, "nivel").send_keys("10")
        self.driver.find_element(By.NAME, "foto").send_keys("https://upload.wikimedia.org/wikipedia/en/0/03/Walter_White_S5B.png")
        self.take_screenshot("crear_formulario_llenado")
        self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()

        WebDriverWait(self.driver, 10).until(
            EC.presence_of_element_located((By.XPATH, "//td[contains(text(),'Walter White')]"))
        )
        self.take_screenshot("crear_personaje_exitoso")

    def test_3_editar_personaje(self):
        """Test de edición de personaje existente"""
        self.driver.get(f"{self.base_url}/index.php")
        edit_btn = WebDriverWait(self.driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, "//tr[1]//a[contains(@class,'btn-warning')]"))
        )
        edit_btn.click()

        WebDriverWait(self.driver, 10).until(
            EC.presence_of_element_located((By.XPATH, "//h2[contains(text(),'Editar')]"))
        )
        self.take_screenshot("editar_formulario_vacio")

        nombre_field = self.driver.find_element(By.NAME, "nombre")
        nivel_field = self.driver.find_element(By.NAME, "nivel")

        nombre_field.clear()
        nivel_field.clear()
        self.take_screenshot("editar_formulario_limpio")

        nombre_field.send_keys("Walter White Modificado")
        nivel_field.send_keys("9")
        self.take_screenshot("editar_formulario_llenado")

        self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()

        WebDriverWait(self.driver, 10).until(
            EC.presence_of_element_located((By.XPATH, "//td[contains(text(),'Walter White Modificado')]"))
        )
        self.take_screenshot("edicion_exitosa")
        self.assertIn("index.php", self.driver.current_url)

    def test_4_eliminar_personaje(self):
        """Test de eliminación de personaje"""
        self.driver.get(f"{self.base_url}/index.php")
        registros_antes = len(self.driver.find_elements(By.XPATH, "//tbody/tr"))

        delete_btn = WebDriverWait(self.driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, "//tr[1]//a[contains(@class,'btn-danger')]"))
        )
        self.take_screenshot("eliminar_confirmacion")
        delete_btn.click()

        try:
            WebDriverWait(self.driver, 3).until(EC.alert_is_present())
            alert = self.driver.switch_to.alert
            alert.accept()
        except:
            pass

        WebDriverWait(self.driver, 10).until(
            EC.invisibility_of_element_located((By.XPATH, "//td[contains(text(),'Walter White Modificado')]"))
        )
        self.take_screenshot("eliminado_despues")

        registros_despues = len(self.driver.find_elements(By.XPATH, "//tbody/tr"))
        self.assertEqual(registros_antes - 1, registros_despues)

    def test_5_generar_pdf(self):
        """Test de generación de PDF de personaje"""
        self.driver.get(f"{self.base_url}/index.php")
        pdf_btn = WebDriverWait(self.driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, "//tr[1]//a[contains(@class,'btn-primary')]"))
        )
        pdf_btn.click()
        self.take_screenshot("despues_de_generalpdf")

    def test_6_logout(self):
        """Test de cierre de sesión"""
        self.driver.get(f"{self.base_url}/index.php")

        logout_btn = WebDriverWait(self.driver, 10).until(
            EC.element_to_be_clickable((
                By.XPATH,
                "//a[contains(@href, 'logout') or contains(text(),'Cerrar sesión') or contains(text(),'Logout')]"
            ))
        )
        self.take_screenshot("antes_logout")
        logout_btn.click()

        WebDriverWait(self.driver, 10).until(
            EC.presence_of_element_located((By.NAME, "username"))
        )
        self.take_screenshot("despues_logout")

if __name__ == "__main__":
    unittest.main(verbosity=2)