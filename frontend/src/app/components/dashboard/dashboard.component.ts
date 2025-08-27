import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { AuthService, User } from '../../services/auth.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatSnackBarModule
  ],
  template: `
    <div class="dashboard-container">
      <mat-card class="dashboard-card">
        <mat-card-header>
          <mat-card-title>Welkom!</mat-card-title>
          <mat-card-subtitle>Je bent succesvol ingelogd</mat-card-subtitle>
        </mat-card-header>

        <mat-card-content>
          <div class="user-info" *ngIf="currentUser">
            <p><strong>E-mail:</strong> {{ currentUser.email }}</p>
            <p *ngIf="currentUser.first_name"><strong>Voornaam:</strong> {{ currentUser.first_name }}</p>
            <p *ngIf="currentUser.last_name"><strong>Achternaam:</strong> {{ currentUser.last_name }}</p>
            <p><strong>Account aangemaakt:</strong> {{ currentUser.created_at | date:'dd/MM/yyyy' }}</p>
          </div>

          <div class="actions">
            <button mat-raised-button color="warn" (click)="logout()">
              <mat-icon>logout</mat-icon>
              Uitloggen
            </button>
          </div>
        </mat-card-content>
      </mat-card>
    </div>
  `,
  styles: [`
    .dashboard-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 20px;
    }

    .dashboard-card {
      max-width: 600px;
      width: 100%;
      padding: 20px;
    }

    .user-info {
      margin: 20px 0;
      padding: 16px;
      background-color: #f5f5f5;
      border-radius: 8px;
    }

    .user-info p {
      margin: 8px 0;
      font-size: 16px;
    }

    .actions {
      margin-top: 24px;
      text-align: center;
    }

    mat-card-title {
      color: #1976d2;
      font-size: 24px;
      margin-bottom: 8px;
    }

    mat-card-subtitle {
      color: #666;
      font-size: 14px;
    }
  `]
})
export class DashboardComponent implements OnInit {
  currentUser: User | null = null;

  constructor(
    private authService: AuthService,
    private router: Router,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit(): void {
    this.authService.currentUser$.subscribe(user => {
      this.currentUser = user;
    });

    if (!this.authService.isAuthenticated()) {
      this.router.navigate(['/login']);
    }
  }

  logout(): void {
    this.authService.logout().subscribe({
      next: () => {
        this.snackBar.open('Succesvol uitgelogd!', 'Sluiten', {
          duration: 3000,
          horizontalPosition: 'center',
          verticalPosition: 'top'
        });
        this.router.navigate(['/login']);
      },
      error: () => {
        this.snackBar.open('Er is een fout opgetreden bij het uitloggen.', 'Sluiten', {
          duration: 3000,
          horizontalPosition: 'center',
          verticalPosition: 'top'
        });
      }
    });
  }
}
